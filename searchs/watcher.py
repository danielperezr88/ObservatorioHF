# -*- coding: utf-8 -*-
"""
Created on Tue Dec 01 12:13:00 2015

@author: oz
"""

import inspect, os, os.path
from os import remove, close
#import psutil
import subprocess
from datetime import datetime, timedelta
import time
import logging
import glob
import re
import pwd
import grp

from google.protobuf import timestamp_pb2
from gcloud import storage

from tempfile import mkstemp
import shutil

import MySQLdb.connections

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

def get_files_to_watch(dirname):
    
    import observatoriohf

    conn = MySQLdb.connections.Connection(user=observatoriohf.dbuser,passwd=observatoriohf.dbpassword,
                                          host=observatoriohf.dbhost,db=observatoriohf.dbdatabase)
    
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
            for pline in ["%s = %s\n"%(k,str([v[0] if len(v) == 1 else v])[1:-1]) for k, v in {k:v.replace("'","").split(',') for k, v in patterns.items()}.items()]:
                new_file.write(pline.replace("'","\""))
        else:
            with open(file_path) as old_file:
                for line in old_file:
                    changed = False
                    for pname, pline in [(k,"%s = %s\n"%(k,str([v[0] if len(v) == 1 else v])[1:-1])) for k, v in {k:v.replace("'","").split(',') for k, v in patterns.items()}.items()]:
                        if line.strip().startswith(pname):
                            changed = True
                            new_file.write(pline.replace("'","\""))
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
    
    conf = {
        0 : "keyword_list_filter",
        2 : "consumer_key",
        3 : "consumer_secret",
        5 : "access_secret",
        4 : "access_token"
    }
    
    conf = {x:searchValues[idt] for idt, x in conf.items()}
    
    import observatoriohf    
    
    conf.update({
        'sid' : searchId,
        'dbuser' : observatoriohf.dbuser,
        'dbhost' : observatoriohf.dbhost,
        'db' : observatoriohf.dbdatabase,
        'dbpwd' : observatoriohf.dbpassword
    })
    
    replace(configpyfile,conf)

def launch_py(searchId, searchValues, pythonPath, dirname):
    create_py_files(searchId, searchValues, dirname) # just in case
    dirname = os.path.dirname(inspect.getfile(inspect.currentframe()))
    filename = os.path.join(dirname, "input" + searchId + ".py")
    # start python process
    return str(subprocess.Popen([pythonPath,filename]).pid)

def launch_py_if_stop(searchId, searchValues, pythonPath, dirname):
    pidfile = os.path.join(dirname, "input" + searchId + ".py.pid")
    if os.path.exists(pidfile):
        pid_data = ''
        # check if pid is running
        with open(pidfile, 'r') as f:
            pid_data = f.read()
        if not check_pid(pid_data):
            pid = launch_py(searchId, searchValues, pythonPath, dirname)
            os.remove(pidfile)
            with open(pidfile, 'w') as f:
                f.write(pid)
    else:
        pid = launch_py(searchId, searchValues, pythonPath, dirname)  
        with open(pidfile, 'w') as f:
            f.write(pid)

def stop_py(pid):
    os.popen("kill %s"%(pid,))
    return True

def stop_py_if_run(searchId, searchStr, pythonPath, dirname):
    pidfile = os.path.join(dirname, "input" + searchId + ".py.pid")
    if os.path.exists(pidfile):
        pid_data = ''
        # check if pid is running
        with open(pidfile, 'r') as f:
            pid_data = f.read()
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
        if not check_pid(pid_data):
            pid = str(subprocess.Popen([pythonPath,filename]).pid)
            os.remove(pidfile)
            with open(pidfile, 'w') as f:
                f.write(pid)
    else:
        pid = str(subprocess.Popen([pythonPath,filename]).pid)
        with open(pidfile, 'w') as f:
            f.write(pid)

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
            os.rename(fromFile, toFile)

def maybeCreateDirs(dirnames, base='.'):
    if not isinstance(dirnames,list): dirnames = [dirnames]
    for dirname in [os.path.join(base,dn) for dn in dirnames if dn != '']:
        if not os.path.exists(dirname): os.makedirs(dirname)
                
def main():
    
    ### Create needed directories
    maybeCreateDirs([
            'input','watcher','analysis','analized',
            'pickled_algos'#,os.path.join('analized','error')
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
        if not os.path.exists(os.path.join(WDIR,PYDIR,PICKLEDIR,filename)):
            fp = open(os.path.join(WDIR,PYDIR,PICKLEDIR,filename),'wb')
            b.download_to_file(fp)
            fp.close()
        
    import observatoriohf
    
    ### Generate configanalysis.py & configwatcher.py from bucketed config file
    cfgs = ['dbhost','dbuser','dbpassword','dbdatabase']
    replace(
        os.path.join(WDIR,'config.py'),
        {n:getattr(observatoriohf,n) for n in cfgs}
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


    dirname = os.path.dirname(os.path.realpath(__file__))
    basename = os.path.basename(os.path.realpath(__file__))

    """Start log."""
    outputDir = os.path.join(dirname, "watcher")
    if not os.path.exists(outputDir):
        os.makedirs(outputDir)
    logfilename = os.path.join(outputDir,basename) + ".log"
    logging.basicConfig(filename=logfilename,level=logging.ERROR, format='%(asctime)s %(message)s')
    logging.info('Started')
    
    save_pid()

    """Infinite looop."""
    end = False
    while (not end):
        pyfiles = get_files_to_watch(dirname)
        keep_processes_alive(pyfiles, pythonPath, dirname)
        keep_analizer_alive(pythonPath, dirname)
        time.sleep(3) # delays for 3 seconds
    
if __name__ == '__main__':
    main()