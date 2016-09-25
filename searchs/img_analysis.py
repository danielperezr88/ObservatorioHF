# -*- coding: utf-8 -*-
"""
Created on Sun Sep 25 11:17:44 2016

@author: Dani
"""

import logging, inspect
import json
import os

import requests
from dateutil import parser
import MySQLdb.connections as mysqlconn

from google.protobuf import timestamp_pb2
from gcloud import storage

import numpy as np

from time import sleep

import observatoriohf

CONFIG_BUCKET = 'configs-hf'
basepath = inspect.getfile(inspect.currentframe())
dirname = os.path.dirname(basepath)
basename = os.path.basename(basepath)

client = storage.Client()
cblob = client.get_bucket(CONFIG_BUCKET).get_blob('ofapiconfig.py')
fp = open(os.path.join(dirname,'ofapiconfig.py'),'wb')
cblob.download_to_file(fp)
fp.close()

import ofapiconfig


"""
    maybeCreateDirs(<list|string>) -> <> (os)
        Creates directories on given paths if they don't exist yet.
"""
def maybeCreateDirs(dirnames):
    if not isinstance(dirnames,list): dirnames = [dirnames]
    for dirname in [dn for dn in dirnames if dn != '']:
        if not os.path.exists(dirname): os.makedirs(dirname)

"""
    save_pid(<>) -> <> (inspect,logging)
        Saves process id as a file with the name of the script file.
"""
def save_pid():
    """Save pid into a file: filename.pid."""
    try:
        pidfilename = inspect.getfile(inspect.currentframe()) + ".pid"
        f = open(pidfilename, 'w') #It will be removed by index.php if it is not working.
        f.write(str(os.getpid()))
        f.close()
    except BaseException as e:
        logging.error('Failed to create pid file: '+ str(e))


def analyzeTweets():

    db_date_suffix = parser.datetime.datetime.now().strftime("%Y_%m")
    
    fetch_pht = "select t1.pht_id as pht_id, t2.cnt_id as cnt_id, t2.url as url from pht_whats_inferred_" + db_date_suffix + " AS t1 JOIN pht_scraped_" + db_date_suffix + " AS t2 ON t1.pht_id=t2.pht_id where t1.age = 0 or t1.gender = 0 order by t1.pht_id desc limit 10000"
    update_pht_extra = "update pht_extra_" + db_date_suffix + " set gender = %s, age = %s, is_multiface = %s where pht_id = %s limit 1"
    update_pht_inferred = "update pht_whats_inferred_" + db_date_suffix + " set gender = %s, age = %s, is_multiface = %s where pht_id = %s limit 1"
    update_extra_gender_age = "update cnt_extra_" + db_date_suffix + " set gender = %s, age = %s where cnt_id = %s limit 1"
    
    dbconfig = dict(user=observatoriohf.dbuser,passwd=observatoriohf.dbpassword,
                    host=observatoriohf.dbhost,db=observatoriohf.dbdatabase)
        
    conn = mysqlconn.Connection(**dbconfig)
    cur = conn.cursor()
    
    try:
        
        cur.execute(fetch_pht)
        conn.commit()
        for pht_id, cnt_id, url in [tuple(row) for row in cur]:
    
            headers = {'Content-Type':'application/json'}
            age = 255
            age_inferred = 0
            gender = 0
            gender_inferred = 0
            error = 0
            
            data = json.dumps(dict(image=url,cuda=False,classifierModel='age_classifier.pkl'))
            r = requests.get('http://'+ofapiconfig.ip+':8889/api/aligninfer', data=data, headers=headers)
            if(r.status_code==200):
                labels, predictions = r.json()['data']
                if(labels is not None):
                    #Maximum Likelihood classifier
                    age = int(labels[np.argmax(predictions)])
                age_inferred = 1
            else: error += 1
    
            data = json.dumps(dict(image=url,cuda=False,classifierModel='gender_classifier.pkl'))
            r = requests.get('http://'+ofapiconfig.ip+':8889/api/aligninfer', data=data, headers=headers)
            if(r.status_code==200):
                labels, predictions = r.json()['data']
                if(labels is not None):
                    #Mixed Classifier
                    labels = np.array(labels)
                    predictions = np.array(predictions)
                    if(labels[np.argmax(predictions)] != 'u'):
                        gender = int((predictions[labels == 'f']/np.sum(predictions[labels != 'u'])*255-128).round().tolist()[0])
                gender_inferred = 1
            else: error += 1
            
            if error == 2: continue
            
            cur.execute(update_pht_extra, (gender, age, 0, pht_id))
            conn.commit()
            
            cur.execute(update_pht_inferred, (gender_inferred, age_inferred, 0, pht_id))
            conn.commit()
            
            cur.execute(update_extra_gender_age, (gender, age, cnt_id))
            conn.commit()
            
    except mysqlconn.Error as err:
        logging.error(err)

    conn.commit()
    cur.close()
    conn.close()

def main():
    """ Create/Retrieve working dirs """
    logDir = os.path.join(dirname, "img_analysis")
    maybeCreateDirs([logDir])
    
    """ Logging configuration """
    logfilename = os.path.join(logDir,basename) + ".log"
    logging.basicConfig(filename=logfilename,level=logging.ERROR, format='%(asctime)s %(message)s')
    logging.info('Started')
    
    """ Process Id file creation """
    save_pid()

    """Infinite looop."""
    while True:
        analyzeTweets()
        sleep(0.25) # delays for 0.25 seconds
    
if __name__ == '__main__':
    
    main()
