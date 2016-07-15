# -*- coding: utf-8 -*-
"""
Created on Tue Jul 12 11:55:17 2016

@author: Dani
"""

import os
import pwd
import grp
import re
import shutil
from tempfile import mkstemp

from google.protobuf import timestamp_pb2
from gcloud import storage

PICKLE_BUCKET = 'pickles-python'
CONFIG_BUCKET = 'configs-hf'
WDIR = '/var/www/html'
PYDIR = 'searchs'
PICKLEDIR = 'pickled_algos'


def maybeCreateDirs(dirnames, base='.'):
    if not isinstance(dirnames,list): dirnames = [dirnames]
    for dirname in [os.path.join(base,dn) for dn in dirnames if dn != '']:
        if not os.path.exists(dirname): os.makedirs(dirname)

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
fp = open(os.path.join(WDIR,PYDIR,'observatoriohf.py'),'w')
cblob.download_to_file(fp)
fp.close()
blobs = client.get_bucket(PICKLE_BUCKET).list_blobs()
for b in blobs:
    filename = re.sub(r'(?:([^\/]*)[\/$]){2}.*',r'\1',b.id)
    fp = open(os.path.join(WDIR,PYDIR,PICKLEDIR,filename),'w')
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
