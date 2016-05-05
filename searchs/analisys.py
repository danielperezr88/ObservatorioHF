# -*- coding: utf-8 -*-
"""
Created on Tue Dec 01 12:13:00 2015

@author: oz
"""

from __future__ import division
import time
import requests
import json
#from csv import writer
import inspect, os
import os.path
import logging
import configanalisys
import glob

import nltk
from nltk.corpus.util import LazyCorpusLoader
from nltk.corpus.reader import *
from nltk.corpus import stopwords
from nltk.classify.scikitlearn import SklearnClassifier
from nltk.classify import ClassifierI
import pickle
import random
#from statistics import mode
import re

from sklearn.naive_bayes import MultinomialNB, BernoulliNB
from sklearn.linear_model import LogisticRegression, SGDClassifier
from sklearn.svm import SVC, LinearSVC, NuSVC

#from pattern.search import search
#from pattern.es     import parsetree
from dateutil import parser


import mysql.connector
from mysql.connector import errorcode
#from datetime import datetime

from collections import Counter

stop = stopwords.words('spanish')


def Accuracy(classifier, testing_set):
#    classification = dict()
    classification = dict()
    classification["pos"]= [0]*3
    classification["neu"]= [0]*3
    classification["neg"]= [0]*3
    lisKeys = list(classification.keys())
    for (testingSet, category) in testing_set:
        if category not in classification.keys():
            lenKeys = len(classification.keys())
            for n in classification.keys():
                classification[n].append(0)
            classification[category] = [0] * (lenKeys+1)
            lisKeys = list(classification.keys())
        
#        returned = filterText(testingSet)
#        print(returned)
#        print (testingSet)
        c = classifier.classify(testingSet)
        if c not in classification.keys():
            lenKeys = len(classification.keys())
            for n in classification.keys():
                classification[n].append(0)
            classification[c] = [0] * (lenKeys+1)
            lisKeys = list(classification.keys())
        
        pos = lisKeys.index(c)
        
        classification[category][pos] += 1

    # Divide all by total
    for c in classification.keys():
        totalC = sum(classification[c])
        if totalC > 0:
            classification[c] = [x / totalC for x in classification[c]]
    return classification
    

def voteResult(votes):
    ordered = Counter(votes).most_common()
    if len (ordered) > 1:
        if ordered[0][1] == ordered[1][1]:
            return "neu"
        else:
            return ordered[0][0]
    else:
        return ordered[0][0]

class VoteClassifier(ClassifierI):
    def __init__(self, *classifiers):
        self._classifiers = classifiers

    def classify(self, features):
        votes = []
        for c in self._classifiers:
            v = c[0].classify(features)
            votes.append(v)
        
        voted = voteResult(votes)
        choice_votes = votes.count(voted)
        conf = choice_votes / len(votes)
        if (conf > .5):
            return voted
        return "neu"
        
#        return mode(votes)

    def confidence(self, features):
#        votes_count = dict()
#        votes_perc = dict()
        votes = []
        for c in self._classifiers:
            v = c[0].classify(features)
            votes.append(v)
#            if v in votes_perc:
#                votes_perc[v] += c[1]
#                votes_count[v] += 1
#            else:
#                votes_perc[v] = c[1]
#                votes_count[v] = 1
        
        choice_votes = votes.count(voteResult(votes))
        conf = choice_votes / len(votes)
#        conf = votes_perc[mode(votes)] / votes_count[mode(votes)]
        return conf

def save_pid():
    """Save pid into a file: filename.pid."""
    try:
        pidfilename = inspect.getfile(inspect.currentframe()) + ".pid"
        f = open(pidfilename, 'w') #It will be removed by index.php if it is not working.
        f.write(str(os.getpid()))
        f.close()
    except BaseException as e:
        logging.error('Failed to create pid file: '+ str(e))

def tokenizer(text):
    text = re.sub('<[^>]*>', '', text)
    emoticons = re.findall('(?::|;|=)(?:-)?(?:\)|\(|D|P)',
                           text.lower())
    text = re.sub('[\W]+', ' ', text.lower()) \
        + ' '.join(emoticons).replace('-', '')
    tokenized = [w for w in text.split() if w not in stop]
    return tokenized

def find_features(document, word_features):
    words = set(document)
    features = {}
    for w in word_features:
        features[w] = (w in words)

    return features

def pickleLoad(filename):
    outputDir = "pickled_algos"
    fullfilename = os.path.join(outputDir,filename)
    if os.path.exists(fullfilename):
        pickle_file = open(fullfilename, "rb")
        pickleObject = pickle.load(pickle_file)
        pickle_file.close()
        return pickleObject

def pickleDump(pickleObject, filename):
    outputDir = "pickled_algos"
    fullfilename = os.path.join(outputDir,filename)
    if not os.path.exists(outputDir):
        os.makedirs(outputDir)
    save_classifier = open(fullfilename,"wb")
    pickle.dump(pickleObject, save_classifier)
    save_classifier.close()

def loadClassifier(outputdir):
    classifier_filename = os.path.join("pickled_algos", "voted_classifier.pickle") 
    word_features_filename = os.path.join("pickled_algos", "word_features.pickle")
    if os.path.exists(classifier_filename) and os.path.exists(word_features_filename):
        word_features = pickleLoad("word_features.pickle")
#        classifier = pickleLoad("originalnaivebayes.pickle")
#        MNB_classifier = pickleLoad("MNB_classifier.pickle")
#        BernoulliNB_classifier = pickleLoad("BernoulliNB_classifier.pickle")
#        LogisticRegression_classifier = pickleLoad("LogisticRegression_classifier.pickle")
#        SGDClassifier_classifier = pickleLoad("SGDClassifier_classifier.pickle")
#        LinearSVC_classifier = pickleLoad("LinearSVC_classifier.pickle")
#        
#        voted_classifier = VoteClassifier(classifier,
##                                  NuSVC_classifier,
#                                  LinearSVC_classifier,
#                                  SGDClassifier_classifier,
#                                  MNB_classifier,
#                                  BernoulliNB_classifier,
#                                  LogisticRegression_classifier)
        voted_classifier= pickleLoad("voted_classifier.pickle")
        return voted_classifier, word_features
    else:
        criticas_cine = LazyCorpusLoader(
                'criticas_cine', CategorizedPlaintextCorpusReader,
                r'(?!\.).*\.txt', cat_pattern=r'(neg|pos)/.*',
                encoding='utf-8')
#        criticas_cine = LazyCorpusLoader(
#                'criticas_cine_neu', CategorizedPlaintextCorpusReader,
#                r'(?!\.).*\.txt', cat_pattern=r'(neg|neu|pos)/.*',
#                encoding='utf-8')
            
        documents = [(list(criticas_cine.words(fileid)), category)
                     for category in criticas_cine.categories()
                     for fileid in criticas_cine.fileids(category)]
#            
#        document_pos = [(list(criticas_cine.words(fileid)), "pos")
#                        for fileid in criticas_cine.fileids("pos")]
#        document_neg = [(list(criticas_cine.words(fileid)), "neg")
#                        for fileid in criticas_cine.fileids("neg")]
#        document_neu = [(list(criticas_cine.words(fileid)), "neu")
#                        for fileid in criticas_cine.fileids("neu")]
        
        random.shuffle(documents)
        
#        random.shuffle(document_pos)
#        random.shuffle(document_neg)
#        random.shuffle(document_neu)
        
        all_words = []
        
        for w in criticas_cine.words():
            all_words.append(w.lower())
        
#        for w in criticas_cine.words():
#            if not is_filtered(w.lower()):
#                all_words.append(w.lower())
#        
        all_words = nltk.FreqDist(all_words)
        
        #print (all_words.most_common(50))
        
        # Filtering by type of word
        
#        for sample in all_words:
                    
        
        word_features = list(all_words.keys())[:3000]
        pickleDump(word_features, "word_features.pickle")
        
        featuresets = [(find_features(rev, word_features), category) for (rev, category) in documents]
        
#        featuresetpos = [(find_features(rev, word_features), category) for (rev, category) in document_pos]
#        featuresetneg = [(find_features(rev, word_features), category) for (rev, category) in document_neg]
#        featuresetneu = [(find_features(rev, word_features), category) for (rev, category) in document_neu]
        
#        training_set = featuresetpos[:1000]
#        training_set.extend(featuresetneg[:1000])
#        training_set.extend(featuresetneu[:1000])
#        testing_set = featuresetpos[1000:1273]
#        testing_set.extend(featuresetneg[1000:])
#        testing_set.extend(featuresetneu[1000:])

#        pos_feat = [(featuresSet, category) for (featuresSet, category) in featuresets if category == "pos"]
#        neu_feat = [(featuresSet, category) for (featuresSet, category) in featuresets if category == "neu"]
#        neg_feat = [(featuresSet, category) for (featuresSet, category) in featuresets if category == "neg"]
                
        training_set = featuresets[:2000]
        testing_set =  featuresets[2000:]
        classifier = nltk.NaiveBayesClassifier.train(training_set)
#        pickleDump(classifier, "originalnaivebayes.pickle")
    
        NaiveBayesClassifierAccuracy = nltk.classify.accuracy(classifier, testing_set)
        
        print("Original Naive Bayes Algo accuracy percent:", (NaiveBayesClassifierAccuracy)*100)
        
        accuracy = Accuracy(classifier,testing_set)
        print(accuracy)
        # order: neu, neg, pos
#        print("Accuracy: ", (accuracy["neg"][0]+accuracy["pos"][2])/3)
#        print("Discarded: ", (accuracy["neu"][0]+accuracy["neg"][1]+accuracy["pos"][0])/3)
#        print("Failed: ", (accuracy["neu"][1]+accuracy["neu"][2]+accuracy["neg"][2]+accuracy["pos"][1])/3)
#        print ("Pos:", nltk.classify.accuracy(classifier, pos_feat)*100)
#        print ("Neu:", nltk.classify.accuracy(classifier, neu_feat)*100)
#        print ("Neg:", nltk.classify.accuracy(classifier, neg_feat)*100)
        classifier.show_most_informative_features(15)
        
        MNB_classifier = SklearnClassifier(MultinomialNB())
        MNB_classifier.train(training_set)
        MNB_classifierAccuracy = nltk.classify.accuracy(MNB_classifier, testing_set)
        print("MNB_classifier accuracy percent:", (MNB_classifierAccuracy)*100)
#        pickleDump(MNB_classifier, "MNB_classifier.pickle")
        
        BernoulliNB_classifier = SklearnClassifier(BernoulliNB())
        BernoulliNB_classifier.train(training_set)
        BernoulliNB_classifierAccuracy = nltk.classify.accuracy(BernoulliNB_classifier, testing_set)
        print("BernoulliNB_classifier accuracy percent:", (BernoulliNB_classifierAccuracy)*100)
#        pickleDump(BernoulliNB_classifier, "BernoulliNB_classifier.pickle")
        
        LogisticRegression_classifier = SklearnClassifier(LogisticRegression())
        LogisticRegression_classifier.train(training_set)
        LogisticRegression_classifierAccuracy = nltk.classify.accuracy(LogisticRegression_classifier, testing_set)
        print("LogisticRegression_classifier accuracy percent:", (LogisticRegression_classifierAccuracy)*100)
#        pickleDump(LogisticRegression_classifier, "LogisticRegression_classifier.pickle")
        
        SGDClassifier_classifier = SklearnClassifier(SGDClassifier())
        SGDClassifier_classifier.train(training_set)
        SGDClassifier_classifierAccuracy = nltk.classify.accuracy(SGDClassifier_classifier, testing_set)
        print("SGDClassifier_classifier accuracy percent:", (SGDClassifier_classifierAccuracy)*100)
#        pickleDump(SGDClassifier_classifier, "SGDClassifier_classifier.pickle")
        
        LinearSVC_classifier = SklearnClassifier(LinearSVC())
        LinearSVC_classifier.train(training_set)
        LinearSVC_classifierAccuracy = nltk.classify.accuracy(LinearSVC_classifier, testing_set)
        print("LinearSVC_classifier accuracy percent:", (LinearSVC_classifierAccuracy)*100)
#        pickleDump(LinearSVC_classifier, "LinearSVC_classifier.pickle")
        
#        SVC_classifier = SklearnClassifier(SVC())
#        SVC_classifier.train(training_set)
#        print("SVC_classifier accuracy percent:", (nltk.classify.accuracy(SVC_classifier, testing_set))*100)
        
        NuSVC_classifier = SklearnClassifier(NuSVC())
        NuSVC_classifier.train(training_set)
        NuSVC_classifierAccuracy = nltk.classify.accuracy(NuSVC_classifier, testing_set)
        print("NuSVC_classifier accuracy percent:", (NuSVC_classifierAccuracy)*100)
        #        pickleDump(LinearSVC_classifier, "LinearSVC_classifier.pickle")
        
        
#        pickleDump([NaiveBayesClassifierAccuracy, 
#                    LinearSVC_classifierAccuracy,
#                    SGDClassifier_classifierAccuracy,
#                    MNB_classifierAccuracy,
#                    BernoulliNB_classifierAccuracy,
#                    LogisticRegression_classifierAccuracy], "accuracies.pickle")
        
        voted_classifier = VoteClassifier([classifier,NaiveBayesClassifierAccuracy],
                                          [NuSVC_classifier,NuSVC_classifierAccuracy],
                                          [LinearSVC_classifier,LinearSVC_classifierAccuracy],
                                          [SGDClassifier_classifier,SGDClassifier_classifierAccuracy],
                                          [MNB_classifier,MNB_classifierAccuracy],
                                          [BernoulliNB_classifier,BernoulliNB_classifierAccuracy],
                                          [LogisticRegression_classifier,LogisticRegression_classifierAccuracy])

        accuracy = Accuracy(voted_classifier,testing_set)
        print(accuracy)
        VoteClassifierAccuracy = nltk.classify.accuracy(voted_classifier, testing_set)
        print("VoteClassifier accuracy percent:", (VoteClassifierAccuracy)*100)
#        print ("Pos:", nltk.classify.accuracy(voted_classifier, pos_feat)*100)
#        print ("Neu:", nltk.classify.accuracy(voted_classifier, neu_feat)*100)
#        print ("Neg:", nltk.classify.accuracy(voted_classifier, neg_feat)*100)
        print("Accuracy: ", (accuracy["neg"][0]+accuracy["pos"][2])/2)
        print("Discarded: ", (accuracy["neu"][1]+accuracy["neg"][1]+accuracy["pos"][1])/2)
        print("Failed: ", (accuracy["neu"][0]+accuracy["neu"][2]+accuracy["neg"][2]+accuracy["pos"][0])/2)
        print("------------------------------------------");
                                          
        pickleDump(voted_classifier, "voted_classifier.pickle")

        return voted_classifier, word_features

def notNullVal(value):
    if value == None:
        return ''
    return value

#def createTable(dbcon, tablename):
#    dbcur = dbcon.cursor()
#    dbcur.execute("""
#        CREATE TABLE `{0}` (
#          `proj_name` varchar(24) NOT NULL,
#          `id` varchar(24) NOT NULL,
#          `created_at` datetime NOT NULL,
#          `name` text NOT NULL,
#          `id_str` text NOT NULL,
#          `location` text,
#          `lang` text NOT NULL,
#          `geo` text,
#          `text` varchar(142) DEFAULT NULL,
#          `sentiment` varchar(3) NOT NULL,
#          `confidence` float NOT NULL
#        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
#        """.format(tablename.replace('\'', '\'\'')))
#    
#def createTableIfNotExists(dbcon, tablename):
#    dbcur = dbcon.cursor()
#    dbcur.execute("""
#        SELECT COUNT(*)
#        FROM information_schema.tables
#        WHERE table_name = '{0}'
#        """.format(tablename.replace('\'', '\'\'')))
#    if (dbcur.fetchone()[0] == 1):
#        dbcur.close()
#        return
#
#    dbcur.close()
#    createTable(dbcon, tablename)

geoDictionary = {}

def GetGoogleLocation(location):
    ''' Try it at lesast 3 times before desist '''
    for attempt in range(3):
        try:
            r = requests.get('http://maps.googleapis.com/maps/api/geocode/json', params={'address': location})
            jsonDecoded = json.loads(r.content.decode("utf-8") )
            if (len(jsonDecoded["results"]) > 0):
                geoLoc = jsonDecoded["results"][0]["geometry"]["location"]
                geoDictionary[location] = [geoLoc["lat"], geoLoc["lng"]]
                return geoLoc["lat"], geoLoc["lng"]
            else:
                geoDictionary[location] = ['','']
                return '',''
        except BaseException as e:
            print("Error GetGoogleLocation: %s" % str(e))
            logging.error("Error GeoLocalize: %s" % str(e))
            time.sleep(1)
        else:
            break
    else:
        return '',''

def GeoLocalize(coordinates, location):
    if coordinates == '':
        if location != '':
            ''' Store locations in a dict to avoid to do several times the same ask '''
            # TODO: evaluate the memory needs of this dictionary
            location = location.lower()
            if location in geoDictionary:
                return geoDictionary[location][0], geoDictionary[location][1]
            else:
                return GetGoogleLocation(location)
    else:
        return coordinates['coordinates'][0], coordinates['coordinates'][1]
    return '',''
            

def uploadData(searchId, tweet, sentiment, confidence):
    try:
        date_object = parser.parse(tweet['created_at'])
#        date_object = datetime.strptime(tweet['created_at'], '%a %b %d %H:%M:%S %z %Y')
        date_str = date_object.strftime("%Y-%m-%d %H:%M:%S")
        lat, lon = GeoLocalize(notNullVal(tweet['coordinates']), notNullVal(tweet['user']['location']))
        sentimentVal = 0
        if (sentiment == "pos" ):
            sentimentVal = 1
        elif (sentiment == "neg" ):
            sentimentVal = -1

#        proj_name = configanalisys.project_name
#        print(proj_name)
        conn = mysql.connector.connect(user=configanalisys.dbuser, password=configanalisys.dbpassword,
                                       host=configanalisys.dbhost, database=configanalisys.dbdatabase)

##        createTableIfNotExists(conn, configanalisys.dbtable)            
        retweetedFrom = ''
        retweetedLat = ''
        retweetedLon = ''
        if ('retweeted_status' in tweet):
            retweetedFrom = tweet['retweeted_status']['user']['screen_name']
            retweetedLat, retweetedLon = GeoLocalize('', notNullVal(tweet['retweeted_status']['user']['location']))
        cursor = conn.cursor()
        anadir_registro = ("INSERT INTO tweets (search_id, created_at, name, id_str, location, lang, geo, text, sentiment, confidence, sentimentVal, geoLat, geoLon, retweetedFrom, retweetedLat, retweetedLon) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)")  
        registro = (
                    searchId,
                    #tweet['id_str'],                # tweet_id
                    date_str,                       # tweet_time
                    tweet['user']['screen_name'],   # tweet_author
                    tweet['user']['id_str'],        # tweet_authod_id
                    notNullVal(tweet['user']['location']),       
                    tweet['lang'],                  # tweet_language
                    notNullVal(tweet['coordinates']),       # tweet_geo
                    notNullVal(tweet['text']),      # tweet_text
                    '{}'.format(sentiment),         # sentiment
                    confidence,                     # confidence
                    sentimentVal,                   # 1, 0, -1
                    lat,
                    lon,
                    retweetedFrom,                  # retweeted_tweet_author
                    retweetedLat, 
                    retweetedLon
                    )
#        print (anadir_registro, registro)
#        print (anadir_registro)
        cursor.execute(anadir_registro, registro)    
        conn.commit()
        cursor.close()
        conn.close()
        return True
    except BaseException as e:
        print('Failed to upload data: '+ str(e))
        return False
#    except mysql.connector.Error as err:
#        if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
#            print ("Something is wrong with your user name or password")
#        elif err.errno == errorcode.ER_BAD_DB_ERROR:
#            print ("Database does not exist")
#        else:
#            print (err)

#def is_filtered(word):
#    s = parsetree(word)
#    multisearch = search("NP|NN|JJ|VP|VB|RB|ADJP", s)
#    toReturn = ""
#    for matches in multisearch:
#        for word in multisearch:
#            toReturn += " " + word.string
#    if toReturn == "":
##        print (word)
#        return True
#    
#    return False

#def filterText(text):
#    s = parsetree(text)
#    multisearch = search("NNS|NNPS|NNP|NP|NN|JJ|JJR|JJS|VP|VB|VBZ|VBP|VBD|VBN|VBG|RB|ADJP", s)
#    toReturn = ""
#    for matches in multisearch:
#        for word in multisearch:
#            toReturn += " " + word.string
#
#    return toReturn

def analizeLine(searchId, line, classifier, word_features):
    if line not in ['\n', '\r\n']:
        try:
            tweet = json.loads(line)
            """Analize sentiment."""
            # text cleaning /////////////////////////////////////////////////////////////////////////////////////////////////////////////
            text = tweet['text'].replace("\/", "_").replace("@", "").replace("!", "").replace("á", "a").replace("é", "e").replace(":", "")
#            text = filterText(text)
#            print(text)
            feats = find_features(text, word_features)
            sentiment_value = classifier.classify(feats)
            confidence = classifier.confidence(feats)
            """Save it into db."""
            return uploadData(searchId, tweet, sentiment_value, confidence)
        except ValueError:
            # TODO: Save it into a file
            print("Error found to be saved:", line)
            return False
        except BaseException as e:
            logging.error('Failed to analizeLine: '+ str(e))
            return False
    return True

def evaluateFiles(classifier, word_features, inputDir, outputDir):
    files = glob.glob(os.path.join(inputDir, "*"))
    for file in files:
        correct = True
        with open(file) as in_file:
            searchId = file.split('_input')[1].split('.')[0]
            for line in in_file:
                correct = correct & analizeLine(searchId,line, classifier, word_features)
            in_file.close()
        if (correct):
            """Move used file to output dir."""
            usedFile = os.path.join(outputDir, os.path.basename(file))
            os.rename(file, usedFile)
        else:
            errorDir = os.path.join(outputDir, "error")
            if not os.path.exists(errorDir):
                os.makedirs(errorDir)
            errorFile = os.path.join(errorDir, os.path.basename(file))
            os.rename(file, errorFile)
            

def main():
    dirname = os.path.dirname(inspect.getfile(inspect.currentframe()))
    basename = os.path.basename(inspect.getfile(inspect.currentframe()))
    """Start log."""
    inputDir = os.path.join(dirname, configanalisys.inputDir)
    if not os.path.exists(inputDir):
        os.makedirs(inputDir)
    outputDir = os.path.join(dirname, configanalisys.outputDir)
    if not os.path.exists(outputDir):
        os.makedirs(outputDir)
    logfilename = os.path.join(outputDir,basename) + ".log"
    logging.basicConfig(filename=logfilename,level=logging.DEBUG, format='%(asctime)s %(message)s')
    logging.info('Started')
    save_pid()
    
    voted_classifier,word_features = loadClassifier("sentiment")
    
    """Infinite looop."""
    end = False
    while (not end):
        evaluateFiles(voted_classifier, word_features, inputDir, outputDir)
        time.sleep(2) # delays for 2 seconds

    logging.info('Finished')
    
if __name__ == '__main__':
    main()
