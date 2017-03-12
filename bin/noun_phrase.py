import sys
from textblob import TextBlob

TextBlob("").noun_phrases

while True:
    s = sys.stdin.readline()
    if not s:
        break
    try:
        print max(TextBlob(s.strip()).noun_phrases, key=len)
    except:
        print ''
