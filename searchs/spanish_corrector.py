# -*- coding: utf-8 -*-
"""
Created on Wed May 11 14:06:35 2016

@author: Dani
"""

import re, collections, pickle, sys

def words(text): return re.findall('[a-záéíóúàèìòùâêîôûüñ \-]+', text.lower()) 

def train(features):
    model = collections.defaultdict(lambda: 1)
    for f in features:
        model[f] += 1
    return model

dictRAE = open('pickled_algos\\dictRAE2010_spanish_tilded_notPref_notSuff.txt','rb')
NWORDS = train(words(dictRAE.read().decode()))
dictRAE.close()

for count in range(0,102):
    fp2 = open('pickled_algos\\complete_parsed_books_dict'+str(count)+'.pickle','rb')
    books = pickle.load(fp2)
    fp2.close()
    for name, each in books.items():
        for word in each:
            NWORDS[word] += 1
            
    print(count,end=", ")
    sys.stdout.flush()

alphabet = 'abcdefghijklmnopqrstuvwxyzáéíóúàèìòùâêîôûüñ -'

def edits1(word):
   splits     = [(word[:i], word[i:]) for i in range(len(word) + 1)]
   deletes    = [a + b[1:] for a, b in splits if b]
   transposes = [a + b[1] + b[0] + b[2:] for a, b in splits if len(b)>1]
   replaces   = [a + c + b[1:] for a, b in splits for c in alphabet if b]
   inserts    = [a + c + b     for a, b in splits for c in alphabet]
   return set(deletes + transposes + replaces + inserts)

def known_edits2(word):
    return set(e2 for e1 in edits1(word) for e2 in edits1(e1) if e2 in NWORDS)

def known(words): return set(w for w in words if w in NWORDS)
  
def memoize(f):
    """ Memoization decorator for functions taking one or more arguments. """
    class memodict(dict):
        def __init__(self, f):
            self.f = f
        def __call__(self, *args):
            return self[args]
        def __missing__(self, key):
            ret = self[key] = self.f(*key)
            return ret
    return memodict(f)
    
@memoize
def correct(word):
    candidates = known([word]) or known(edits1(word)) or known_edits2(word) or [word]
    return max(candidates, key=NWORDS.get)
    #return candidates, NWORDS
    