# -*- coding: utf-8 -*-
"""
Created on Tue Dec 01 12:13:00 2015

@author: oz
"""

#import tweepy
from tweepy import Stream
from tweepy import OAuthHandler
from tweepy.streaming import StreamListener
import time
import string
#import config
import json
import inspect, os
import os.path
import logging
from datetime import datetime
#import configinput

class MyListener(StreamListener):
    """Custom StreamListener for streaming data."""

    def __init__(self, data_dir, to_dir, query):
        self.data_dir = data_dir
        self.to_dir = to_dir
        self.query_fname = format_filename(query)
        datetimeStr = datetime.now().strftime("%Y%m%d_%H%M%S")
        self.outfile = "%s/%s_stream_%s.json" % (self.data_dir, datetimeStr, self.query_fname)
        self.previousDatetimeStr = datetimeStr

    def on_data(self, data):
        try:
            datetimeStr = datetime.now().strftime("%Y%m%d_%H%M%S")
            if (self.previousDatetimeStr != datetimeStr):
#                if os.path.exists(self.outfile):
#                    move_file(self.outfile, self.to_dir)
                self.outfile = "%s/%s_stream_%s.json" % (self.data_dir, datetimeStr, self.query_fname)
                self.previousDatetimeStr = datetimeStr
            with open(self.outfile, 'a') as f:
                f.write(data)
#                print(data)
                f.close()
                return True
        except BaseException as e:
#            print("Error on_data: %s" % str(e))
            logging.error("Error on_data (%s): %s", self.outfile, str(e))
            time.sleep(5)
        return True

    def on_error(self, status):
        print(status)
        return True


def format_filename(fname):
    """Convert file name into a safe string.
    Arguments:
        fname -- the file name to convert
    Return:
        String -- converted file name
    """
    return ''.join(convert_valid(one_char) for one_char in fname)


def convert_valid(one_char):
    """Convert a character into '_' if invalid.
    Arguments:
        one_char -- the char to convert
    Return:
        Character -- converted char
    """
    valid_chars = "-_.%s%s" % (string.ascii_letters, string.digits)
    if one_char in valid_chars:
        return one_char
    else:
        return '_'

@classmethod
def parse(cls, api, raw):
    status = cls.first_parse(api, raw)
    setattr(status, 'json', json.dumps(raw))
    return status

def save_pid():
    """Save pid into a file: filename.pid."""
    try:
        pidfilename = inspect.getfile(inspect.currentframe()) + ".pid"
        f = open(pidfilename, 'w') #It will be removed by index.php if it is not working.
        f.write(str(os.getpid()))
        f.close()
    except BaseException as e:
        logging.error('Failed to create pid file: '+ str(e))
            
def move_file(fromFile, dirname):
    try:
        toFile = os.path.join(dirname, os.path.basename(fromFile))
        os.rename(fromFile, toFile)
    except BaseException as e:
        logging.error('Failed to move_file file: '+ str(e))
        
def main():
#    dirname = os.path.dirname(inspect.getfile(inspect.currentframe()))
#    basename = os.path.basename(inspect.getfile(inspect.currentframe()))
    dirname = os.path.dirname(os.path.realpath(__file__))
    basename = os.path.basename(os.path.realpath(__file__))
    name_noextension = os.path.splitext(basename)[0]
    """Start log."""
    configinput = __import__("config" + name_noextension)
    
    #toDir = os.path.join(dirname, configinput.to_dir)
    toDir = os.path.join(dirname, "analysis")
    
    #outputDir = os.path.join(dirname, configinput.directory)
    outputDir = os.path.join(dirname, "input")
    if not os.path.exists(outputDir):
        os.makedirs(outputDir)
    logfilename = os.path.join(outputDir,basename) + ".log"
    logging.basicConfig(filename=logfilename,level=logging.DEBUG, format='%(asctime)s %(message)s')
    logging.info('Started')
    save_pid()


    """Execute the twitter api."""
    try:
        auth = OAuthHandler(configinput.consumer_key, configinput.consumer_secret)
        auth.set_access_token(configinput.access_token, configinput.access_secret)
        twitter_stream = Stream(auth, MyListener(outputDir, toDir, basename))   # el segundo argumento es el nombre del archibvo json
        twitter_stream.filter(track=configinput.keyword_list_filter,languages=['es'])
    except BaseException as e:
        logging.error('Failed to execute twitter api: ' + str(e))    
    
    logging.info('Finished')
    
if __name__ == '__main__':
    main()
