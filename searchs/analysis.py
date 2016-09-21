# -*- coding: utf-8 -*-
"""
Created on Thu May 19 11:48:33 2016

@author: Dani
"""

import os, sys, glob

import importlib
importlib.reload(sys)

import logging, inspect
from dateutil import parser

import json, pickle
import unicodedata
import requests
import regex as re

from time import sleep

import nltk

import MySQLdb.connections as mysqlconn

from sklearn.feature_extraction.text import CountVectorizer

import spanish_corrector as sc
import observatoriohf

"""                                 DECORATORS                              """

"""
    @memoize(<function>) (unicodedata)
        Handles function input/output caching.
"""
def memoize(f):
  class memodict(dict):
      __slots__ = ()
      def __missing__(self, key):
          self[key] = ret = f(key)
          return ret
  return memodict().__getitem__

"""                                 UTILITIES                               """

"""
    sign(<float|int>) -> <int>
        Returns -1/0/1 for cases input<0/input==0/input>0 respectively
"""
sign = lambda x: int(x>0) - int(x<0)

"""
    removeAccents(<string>) -> <string> (unicodedata)
        Returns input string without accents.
"""
def removeAccents(input_str):
    nfkd_form = unicodedata.normalize('NFKD', input_str)
    return u"".join([c for c in nfkd_form if not unicodedata.combining(c)])

"""
    maybeCreateDirs(<list|string>) -> <> (os)
        Creates directories on given paths if they don't exist yet.
"""
def maybeCreateDirs(dirnames):
    if not isinstance(dirnames,list): dirnames = [dirnames]
    for dirname in [dn for dn in dirnames if dn != '']:
        if not os.path.exists(dirname): os.makedirs(dirname)

"""
    pickleLoad(<string>) -> <mixed> (pickle)
        Handles pickle load from file with given name.
"""
def pickleLoad(filename):
    pickle_file = open(filename, "rb")
    pickleObject = pickle.load(pickle_file)
    pickle_file.close()
    return pickleObject

"""
    pickleDump(<mixed>,<string>) -> <> (os, re, pickle, util:maybeCreateDirs)
        Handles pickle dump of given content on file with given filename.
"""
def pickleDump(pickleObject, filename):
    maybeCreateDirs("/".join(re.split(r'[\\\/]+',filename)[:-1]))
    save_classifier = open(filename,"wb")
    pickle.dump(pickleObject, save_classifier)
    save_classifier.close()
    
"""
    rawLoad(<string>) -> <string> ()
        Loads raw content from file with the given name.
"""
def rawLoad(filename):
    fp = open(filename,'rb')
    result = fp.read().decode()
    fp.close()
    return result

"""
    rawDump(<string>,<string>) -> <> (os, re, util:maybeCreateDirs)
        Saves raw content on a file with the given name.
"""
def rawDump(content, filename):
    maybeCreateDirs("/".join(re.split(r'[\\\/]+',filename)[:-1]))
    fp = open(filename,'wb')
    fp.write(content)
    fp.close()

"""
    loadDependency(<string>,<string>) -> <mixed> (os, re, pickle, util:pickleLoad)
        Loads file based dependencies, wether pickled or raw formatted.
"""
def loadDependency(name,basepath="."):
    filename = os.path.join(basepath,name)
    if not os.path.exists(filename): return False
        
    extension = re.split(r'[\.]+',name)[-1]
    if extension in ('pkl','pickle'):
        return pickleLoad(filename)
    else:
        return rawLoad(filename)
        
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

"""                                 FUNCTIONS                               """          

"""

"""
def find_features(document):
    
    from nltk.tokenize import word_tokenize
    from nltk.corpus import stopwords

    """ Preprocess content """
    # document = quitar_acentos(re.sub(r'[?|$|.|!|¡|¿|,|:|;]',r'',document).lower())
    document = re.sub(
        r'((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[.\!\/\\w]*))?)',
        r' ', document).lower()
    document = re.sub(r'[.|!|¡|¿|,|:|;|_]', r' ', document).lower()

    """ Tokenize """
    count_vect = CountVectorizer(stop_words=None, token_pattern='(?u)\\b\\w+\\b')
    words = count_vect.build_analyzer().__call__(document.replace('?', 'ë'))

    """ Maybe Spell Correct + Rule-Based Pruning """
    corrected = []
    for each in words:

        """ RULE-BASED PRUNING """

        """ Words not containing usual language characters """
        if not re.search(r'[a-zA-Záéíóúàèìòùîüñ \-]', each):
            continue
        """ URLs """
        if re.match(r'http.*', each):
            continue
        """ RT twitter retweeted special word """
        if each.lower() == 'rt':
            continue
        """ @ twitter user reference special prefix """
        if re.match(r'@.*', each):
            continue
        """ # twitter hashtag special prefix """
        if re.match(r'#.*', each):
            continue
        """ Words containing numbers """
        if re.match(r'[^0-9]*[0-9]', each):
            continue

        """ MAYBE SPELL CORRECT """

        """ Condition for Spell Correct: Word contains symbols """
        if re.search(r'[^a-zA-Záéíóúàèìòùîüñ \-]', each):
            corrected.append(sc.correct(each))
        else:
            corrected.append(each)

    """ Stop-word Pruning """
    stop_words = set(stopwords.words("spanish"))
    features = [w for w in corrected if w not in stop_words]

    """ Filtered features  """
    return features
      
def retrieveClassAndConfidence(classifier,feats=list()):

    if not bool(feats):
        #return 'unk', 0
        return 0, 0
        
    probs = classifier.predict_proba([' '.join(feats)])[0]
    #result = 'neg' if sorted(range(len(probs)), key=probs.__getitem__, reverse=True)[0] is 0 else 'pos'
    
    #return result, int(probs[1]*255)-128
    return int(probs[1]*255)-128, 1
    
@memoize
def GetGoogleLocation(location):
    ''' Try it at lesast 3 times before desist '''
    for attempt in range(3):
        try:
            r = requests.get('http://maps.googleapis.com/maps/api/geocode/json', params={'address': location})
            jsonDecoded = json.loads(r.content.decode("utf-8") )
            if (len(jsonDecoded["results"]) > 0):
                geoLoc = jsonDecoded["results"][0]["geometry"]["location"]
                return geoLoc["lat"], geoLoc["lng"]
            else:
                return 0, 0
        except BaseException as e:
            print("Error GetGoogleLocation: %s" % str(e))
            logging.error("Error GeoLocalize: %s" % str(e))
            sleep(1)
        else:
            break
    else:
        return 0, 0

def GeoLocalize(location):
        
    if (location if location != None else '') != '':
        return GetGoogleLocation(location.lower()) + (1,)
    
    return 0, 0, 0

def analizeTweets(classifier, word_features):
    
    dbconfig = dict(user=observatoriohf.dbuser,passwd=observatoriohf.dbpassword,
                    host=observatoriohf.dbhost,db=observatoriohf.dbdatabase)
    
    conn = mysqlconn.Connection(**dbconfig)
    cur = conn.cursor()
    
    db_date_suffix = parser.datetime.datetime.now().strftime("%Y_%m")
    
    fetch_unclassified = "select cnt_id, geolat, geolon from cnt_extra_" + db_date_suffix + " where polarity = 0 limit 10000"
    fetch_content = "select content from cnt_scraped_" + db_date_suffix + " where cnt_id = %s limit 1"
    fetch_original = "select original_lat, original_lon, original_location from cnt_interactions_" + db_date_suffix + " where cnt_id = %s limit 1"
    fetch_location = "select location from cnt_info_" + db_date_suffix + " where cnt_id = %s limit 1"
    update_extra = "update cnt_extra_" + db_date_suffix + " set polarity = %s, geolat = %s, geolon = %s where cnt_id = %s limit 1"
    update_extra_without_geo = "update cnt_extra_" + db_date_suffix + " set polarity = %s where cnt_id = %s limit 1"
    update_extra_without_pol = "update cnt_extra_" + db_date_suffix + " set geolat = %s, geolon = %s where cnt_id = %s limit 1"
    update_interactions = "update cnt_interactions_" + db_date_suffix + " set original_lat = %s, original_lon = %s where cnt_id = %s limit 1"
    update_inferred = "update cnt_whats_inferred_" + db_date_suffix + " set geo = %s, original_geo = %s, polarity = %s where cnt_id = %s limit 1"
    
    try:

        cur.execute(fetch_unclassified)
        for cnt_id, geoLat, geoLon in [tuple(row) for row in cur]:

            conn.commit()
            cur.execute(fetch_content,(cnt_id))
            content = cur.fetchone()[0]
            
            conn.commit()
            cur.execute(fetch_original,(cnt_id))
            original_lat, original_lon, original_location = tuple([d for d in cur[0]])
            
            conn.commit()
            cur.execute(fetch_location,(cnt_id))
            location = cur.fetchone()[0]
            
            feats = find_features(content)
            #sentiment, confidence = retrieveClassAndConfidence(classifier, feats)
            polarity, pol = retrieveClassAndConfidence(classifier, feats)
            geoLat, geoLon, geo = GeoLocalize(location) if any([geoLat != 0, geoLon != 0]) else (geoLat, geoLon, 0)
            original_lat, original_lon, original_geo = GeoLocalize(original_location) if any([original_lat != 0, original_lon != 0]) else (original_lat, original_lon, 0)
            
            if geo == 1 and pol == 1:
                conn.commit()
                cur.execute(update_extra, (polarity, geoLat, geoLon, cnt_id))
            elif pol == 1:
                conn.commit()
                cur.execute(update_extra_without_geo, (polarity, cnt_id))                
            elif geo == 1:
                conn.commit()
                cur.execute(update_extra_without_pol, (geoLat, geoLon, cnt_id))                
            
            if original_geo == 1:
                conn.commit()
                cur.execute(update_interactions, (original_lat, original_lon, cnt_id))
            
            conn.commit()
            cur.execute(update_inferred, (geo, original_geo, pol, cnt_id))

    except mysqlconn.Error as err:
        logging.error(err)

    conn.commit()
    cur.close()
    conn.close()
            
"""                                   MAIN                                  """

def main():
    """ Create/Retrieve working dirs """
    dirname = os.path.dirname(inspect.getfile(inspect.currentframe()))
    inputDir = os.path.join(dirname, "analysis")
    outputDir = os.path.join(dirname, "analized")
    maybeCreateDirs([inputDir,outputDir])    
    
    """ Maybe download nltk dependencies """
    maybeCreateDirs(['/root/nltk_data'])
    if not os.path.exists('/root/nltk_data/tokenizers/punkt'):
        nltk.download('punkt','/root/nltk_data')
    if not os.path.exists('/root/nltk_data/corpora/stopwords'):
        nltk.download('stopwords','/root/nltk_data')
    
    """ Logging configuration """
    basename = os.path.basename(inspect.getfile(inspect.currentframe()))
    logfilename = os.path.join(outputDir,basename) + ".log"
    logging.basicConfig(filename=logfilename,level=logging.ERROR, format='%(asctime)s %(message)s')
    logging.info('Started')
    
    """ Process Id file creation """
    save_pid()
    
    """ Dependency retrieval """    
    classifier = loadDependency("class_grid_search.pickle",os.path.join(dirname,"pickled_algos"))
    word_features = sc.words(loadDependency("dictRAE2010_spanish_tilded_notPref_notSuff.txt",os.path.join(dirname,"pickled_algos")))
    
    """Infinite looop."""
    while True:
        analizeTweets(classifier, word_features)
        sleep(0.25) # delays for 0.25 seconds

    logging.info('Finished')
    
if __name__ == '__main__':
    
    main()

