# -*- coding: utf-8 -*-
"""
Created on Tue Dec 01 12:13:00 2015

@author: oz
"""

import inspect, os, os.path
from os import remove, close
#import psutil
#import subprocess
from datetime import datetime, timedelta
import time
import configwatcher
import configparser
import logging
import glob
import re
import pwd
import grp

from google.protobuf import timestamp_pb2
from gcloud import storage

from tempfile import mkstemp
import shutil

import mysql.connector
from mysql.connector import errorcode
from sys import executable as pythonPath


PICKLE_BUCKET = 'pickles-python'
CONFIG_BUCKET = 'configs-hf'
WDIR = '/var/www/html'
PYDIR = 'searchs'
PICKLEDIR = 'pickled_algos'


def save_pid():
    """Save pid into a file: filename.pid."""
    try:
        pidfilename = inspect.getfile(inspect.currentframe()) + ".pid"
        logging.info('PID ' + str(os.getpid()) + ' in ' + pidfilename)
        f = open(pidfilename, 'w')
        f.write(str(os.getpid()))
        f.close()
    except BaseException as e:
        logging.error('Failed to create pid file: '+ str(e))

def check_pid(pid):
    return int(os.popen("ps -p %d --no-headers | wc -l"%(int(pid) if len(pid) > 0 else 0,)).read().strip()) == 1

#    """Check whether pid exists in the current process table.
#    UNIX only.
#    """
#    if pid < 0:
#        return False
#    if pid == 0:
#        # According to "man 2 kill" PID 0 refers to every process
#        # in the process group of the calling process.
#        # On certain systems 0 is a valid PID but we have no way
#        # to know that in a portable fashion.
#        raise ValueError('invalid PID 0')
#    try:
#        os.kill(pid, 0)
#    except OSError as err:
#        if err.errno == errno.ESRCH:
#            # ESRCH == No such process
#            return False
#        elif err.errno == errno.EPERM:
#            # EPERM clearly means there's a process to deny access to
#            return True
#        else:
#            # According to "man 2 kill" possible error values are
#            # (EINVAL, EPERM, ESRCH)
#            raise
#    else:
#        return True
    

def get_files_to_watch(dirname):
    # Get list of py files to check from db
    conn = mysql.connector.connect(user=configwatcher.dbuser, password=configwatcher.dbpassword,
                                   host=configwatcher.dbhost, database=configwatcher.dbdatabase)
    cursor = conn.cursor()
    query = ("SELECT t1.id, t1.search, t1.active, t2.ckey, t2.consumer_secret, t2.access_token_key, t2.access_token_secret FROM `searchs` as t1  LEFT JOIN config as t2 ON t2.id= t1.config_id")
    cursor.execute(query)
    myList= {}
    for (id, search, active, ckey, c_sec, at_k, at_s) in cursor:
        myList[id] = [search,active, ckey, c_sec, at_k, at_s]
    
    cursor.close()
    conn.close()
#    print(myList)
#    filtered = [f for f in pyfiles if 'config' not in os.path.basename(f) and basename not in os.path.basename(f)]
    return myList

def replace(file_path, patterns):
    #Create temp file
    fh, abs_path = mkstemp()
    with open(abs_path,'w') as new_file:
        if not os.path.exists(file_path):
            for pline in [pname + ' = "' + pvalue + '"\n' for pname, pvalue in patterns.items()]:
                new_file.write(pline)
        else:
            with open(file_path) as old_file:
                for line in old_file:
                    changed = False
                    for pname, pline in [(pname,pname + ' = "' + pvalue + '"\n') for pname, pvalue in patterns.items()]:
                        if line.strip().startswith(pname):
                            changed = True
                            new_file.write(pline)
                    if not changed:
                        new_file.write(line)
                
            #Remove original file
            os.remove(file_path)
                
    os.close(fh)
    #Move new file
    shutil.move(abs_path, file_path)
    
def create_py_files(searchId, searchValues, dirname):
    destpyfile = os.path.join(dirname, "input" + searchId + ".py")
    if not os.path.exists(destpyfile):
        shutil.copy(os.path.join(dirname, "input.py"), destpyfile)
    configpyfile = os.path.join(dirname, "configinput" + searchId + ".py")
#    if not os.path.exists(configpyfile):
    shutil.copy(os.path.join(dirname, "configinput.py"), configpyfile)
    
    configinput = {
        0 : "keyword_list_filter",
        2 : "consumer_key",
        3 : "consumer_secret",
        4 : "access_secret",
        5 : "access_token"
    }
    
    replace(configpyfile,{x:searchValues[idt] for idt, x in configinput.items()})

def launch_py(searchId, searchValues, pythonPath, dirname):
    create_py_files(searchId, searchValues, dirname) # just in case
    dirname = os.path.dirname(inspect.getfile(inspect.currentframe()))
    filename = os.path.join(dirname, "input" + searchId + ".py")
    # start python process
#    print(filename)
    os.system(pythonPath + ' ' + filename)

def launch_py_if_stop(searchId, searchValues, pythonPath, dirname):
#    print("launching " + searchId)
    pidfile = os.path.join(dirname, "input" + searchId + ".py.pid")
#    print( pidfile)
    if os.path.exists(pidfile):
        pid_data = ''
        # check if pid is running
        with open(pidfile, 'r') as f:
            pid_data = f.read()
        f.close()
        if not check_pid(pid_data):
            launch_py(searchId, searchValues, pythonPath, dirname)
    else:
        launch_py(searchId, searchValues, pythonPath, dirname)  

def stop_py(pid):
    os.popen("kill %d"%(pid,))
    return True

def stop_py_if_run(searchId, searchStr, pythonPath, dirname):
#    print("Stoping " + searchId)
    pidfile = os.path.join(dirname, "input" + searchId + ".py.pid")
    if os.path.exists(pidfile):
        pid_data = ''
        # check if pid is running
        with open(pidfile, 'r') as f:
            pid_data = f.read()
        f.close()
        if check_pid(pid_data):
            stop_py(pid_data)
        remove(pidfile)

def launch_or_stop(searchId, searchValues, pythonPath, dirname):
    # Get pid file
    if searchValues[1] == 1: #active
        launch_py_if_stop(searchId, searchValues, pythonPath, dirname)
    else: # inactive
        stop_py_if_run(searchId, searchValues[0], pythonPath, dirname)
        

def keep_processes_alive(pyfiles, pythonPath, dirname):
    for searchId, searchVals in pyfiles.items():
        launch_or_stop(str(searchId), searchVals, pythonPath, dirname)

def keep_analizer_alive(pythonPath, dirname):
    analizer_name = "analysis.py"
    filename = os.path.join(dirname, analizer_name)
    pidfile = os.path.join(dirname, analizer_name + ".pid")
    if os.path.exists(pidfile):
        pid_data = ''
        # check if pid is running
        with open(pidfile, 'r') as f:
            pid_data = f.read()
        f.close()
        if not check_pid(pid_data):
            os.system(pythonPath + ' ' + filename)
    else:
        os.system(pythonPath + ' ' + filename)

def get_python_path(dirname):
    config = configparser.RawConfigParser(allow_no_value=True)
    config.readfp(open(os.path.join(dirname, "config.txt"), 'r'))
    return config.get("root", "python_exe")

def num(s):
    try:
        return int(s)
    except ValueError:
        return float(s)

def move_from_to(dir_from, dir_to):
    files = glob.glob(os.path.join(dir_from, "*"))
    filtered = [f for f in files if '.log' not in os.path.basename(f)]
    for fromFile in filtered:
        now = datetime.now()
        datetimeStr = now.strftime("%Y%m%d_%H%M%S")
        oneSecondBefore = now - timedelta(seconds=1)
        oneSecondBeforeStr = oneSecondBefore.strftime("%Y%m%d_%H%M%S")
        twoSecondBefore = now - timedelta(seconds=2)
        twoSecondBeforeStr = twoSecondBefore.strftime("%Y%m%d_%H%M%S")

        basenameFile = os.path.basename(fromFile)
        if (not basenameFile.startswith(datetimeStr) \
            and not basenameFile.startswith(oneSecondBeforeStr) \
            and not basenameFile.startswith(twoSecondBeforeStr)):
            toFile = os.path.join(dir_to, os.path.basename(fromFile))
#            print(fromFile, toFile)
            os.rename(fromFile, toFile)

def move_files(dirname):
    counter = 0
    dir_from = ''
    #for dir_files in configwatcher.from_to_dirs:
    for dir_files in ['input','analysis']:
        if counter % 2 == 0:
            dir_from = os.path.join(dirname, dir_files)
        else:
            move_from_to(dir_from, os.path.join(dirname, dir_files))
        counter += 1
        
def maybeCreateDirs(dirnames, base='.'):
    if not isinstance(dirnames,list): dirnames = [dirnames]
    for dirname in [os.path.join(base,dn) for dn in dirnames if dn != '']:
        if not os.path.exists(dirname): os.makedirs(dirname)
                
def main():
    
    ### Create needed directories
    maybeCreateDirs([
            'input','watcher','analysis','analized',
            os.path.join('analized','error'),'pickled_algos'
        ],
        os.path.join(WDIR,PYDIR)
    )
    
    ### Download pickles and config file from pickle bucket
    client = storage.Client()
    cblob = client.get_bucket(CONFIG_BUCKET).get_blob('observatoriohf.py')
    fp = open(os.path.join(WDIR,PYDIR,'observatoriohf.py'),'wb')
    cblob.download_to_file(fp)
    fp.close()
    blobs = client.get_bucket(PICKLE_BUCKET).list_blobs()
    for b in blobs:
        filename = re.sub(r'(?:([^\/]*)[\/$]){2}.*',r'\1',b.id)
        fp = open(os.path.join(WDIR,PYDIR,PICKLEDIR,filename),'wb')
        b.download_to_file(fp)
        fp.close()
    
    import observatoriohf
        
    
    ### Generate configanalysis.py & configwatcher.py from bucketed config file
    cfgs = ['dbhost','dbuser','dbpassword','dbdatabase']
    cfgfiles = ['configanalysis.py','configwatcher.py']
    for f in cfgfiles:
        replace(
            os.path.join(WDIR,PYDIR,f),
            {n:getattr(observatoriohf,n) for n in cfgs}
        )
    
    ### Copy configanalysis.py into ../config.py
    shutil.copyfile(
        os.path.join(WDIR,PYDIR,'configanalysis.py'),
        os.path.join(WDIR,'config.py')
    )
    
    ### Generate configinput.py (input configuration template)
    configinput = {
        "keyword_list_filter" : [],
        "consumer_key" : "",
        "consumer_secret" : "",
        "access_secret" : "",
        "access_token" : ""
    }
    fp = open(os.path.join(WDIR,PYDIR,'configinput.py'),'w')
    for (key, value) in configinput.items():
        fp.write("%s = %s\n" % (key, str([value]).replace('\n', '\n\t')[1:-1]))
    fp.write("\n")
    fp.close()
    
    ### Set correct ownership for Apache2 to be able to run our webservice
    uid = pwd.getpwnam("www-data").pw_uid
    gid = grp.getgrnam("www-data").gr_gid
    for root, dirs, files in os.walk(WDIR):  
        for momo in dirs:  
            os.chown(os.path.join(root, momo), uid, gid)
        for momo in files:
            os.chown(os.path.join(root, momo), uid, gid)

    
#    dirname = os.path.dirname(inspect.getfile(inspect.currentframe()))
#    basename = os.path.basename(inspect.getfile(inspect.currentframe()))
    dirname = os.path.dirname(os.path.realpath(__file__))
    basename = os.path.basename(os.path.realpath(__file__))

    """Start log."""
    #outputDir = os.path.join(dirname, configwatcher.directory)
    outputDir = os.path.join(dirname, "watcher")
    if not os.path.exists(outputDir):
        os.makedirs(outputDir)
    logfilename = os.path.join(outputDir,basename) + ".log"
    logging.basicConfig(filename=logfilename,level=logging.DEBUG, format='%(asctime)s %(message)s')
    logging.info('Started')
    
    save_pid()
    
#    pyfiles = get_files_to_watch(dirname)
#    print(pyfiles)
#    pythonPath = get_python_path(dirname)
#    keep_processes_alive(pyfiles, pythonPath, dirname)
#
    """Infinite looop."""
    end = False
    while (not end):
        pyfiles = get_files_to_watch(dirname)
#        print(pyfiles)
        keep_processes_alive(pyfiles, pythonPath, dirname)
        keep_analizer_alive(pythonPath, dirname)
        move_files(dirname)
        time.sleep(3) # delays for 3 seconds
#    keep_processes_alive(pyfiles, pythonPath)
    
if __name__ == '__main__':
    main()