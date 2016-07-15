# -*- coding: utf-8 -*-
"""
Created on Tue Jul 12 11:55:17 2016

@author: Dani
"""

import os
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
    

### Generate configanalysis.py & configwatcher.py by prompting the user
"""
configs = {
    'Database Host (IP or hostname)[required]' : { 'required' : True, 'name' : 'dbhost', 'value' : ''},
    'Database User [required]' : { 'required' : True, 'name' : 'dbuser', 'value' : ''},
    'Database User Password (void for none)' : { 'required' : False, 'name' : 'dbpassword', 'value' : ''},
    'Table Name [required]' : { 'required' : True, 'name' : 'dbdatabase', 'value' : ''},
}

print("Welcome to the ObservatorioHF configuration assistant")
print("")
print("Please, fill the following:")
print("")

res = []
for cfname, req in [(n,v['required']) for n,v in configs.items()]:
    r = raw_input(cfname+': ')
    while(len(r) == 0 and req):
        r = raw_input(cfname+': ')
    configs[cfname]['value'] = r
    print("")
"""

cfgs = ['dbhost','dbuser','dbpassword','dbdatabase']

replace('configanalysis.py',{n:getattr(observatoriohf,n) for n in cfgs})
replace('configwatcher.py',{n:getattr(observatoriohf,n) for n in cfgs})

### Generate configinput.py (input configuration template)
configinput = {
    "keyword_list_filter" : [],
    "consumer_key" : "",
    "consumer_secret" : "",
    "access_secret" : "",
    "access_token" : ""
}
fp = open('configinput.py','w')
for (key, value) in configinput.items():
    fp.write("%s = %s\n" % (key, str([value]).replace('\n', '\n\t')[1:-1]))
fp.write("\n")
fp.close()
