# -*- coding: utf-8 -*-
"""
# Name: MoveEpisodes
# What: Move and sort your episodes from your download folder to the right folder (eg. "/<show name>/Season x")
# Why: 'Cause it's better when it's automatic
"""
import re
import platform
import os
import shutil
import string

dlpath = '' # Your download folder
tvpath = '' # Your "Shows" folder

if len(dlpath) == 0 or len(tvpath) == 0:
    print "Pour automatiser, veuillez entrer les dossiers de départ et d'arrivée dans le script (vars 'dlpath' & 'tvpath')\n"
    dlpath = raw_input("Entrer le dossier de départ (ex. /Downloads) : ")
    tvpath = raw_input("Entrer le dossier d'arrivée (ex. /Shows) : ")

if platform.system() == 'Windows':
    os.path.normcase(dlpath)
    os.path.normcase(tvpath)
    endchar = '\\'
else:
    endchar = '/'
    
if not dlpath.endswith(endchar):
    dlpath.append(endchar)
    
if not tvpath.endswith(endchar):
    tvpath.append(endchar)


# Create listFile containing the show name, show season (according to the file), and the filename
listFile = []
for f in os.listdir(dlpath):
    nick = f.lower()
    m = re.search('\.s([0-9]+)', nick)
    saison = m.groups()[0]
    
    if '.s0' in nick or '.s1' in nick:
        nick = listPart[0]
        name = nick.replace('.', ' ')
        name = string.capwords(name)
        listFile.append([name, saison, f])

# Move the files at the right place reading listFile
listMove = []
listDir = os.listdir(tvpath)
for content in listFile:
# content[0] is the the show name (used to create the "/<show name>" folder)
# content[1] is the show season (according to the file)
# content[2] is the filename
    if content[0] in listDir:
        listSubDir = os.listdir(tvpath+content[0])
        if not 'Saison '+content[1] in listSubDir:
            os.makedirs(tvpath+content[0]+'/Saison '+content[1])
    else:
        os.makedirs(tvpath+content[0])
        os.makedirs(tvpath+content[0]+'/Saison '+content[1])

    shutil.move(dlpath+content[2], tvpath+content[0]+'/Saison '+content[1]+'/'+content[2])
    listMove.append(content[2])

# Display the result
print 'Au départ dans %s :' % dlpath
if listFile.__len__() == 0:
    print 'Aucun fichier "déplaçable"\n'
else:
    print listFile.__len__(),'fichier(s) "déplaçable(s)" dans le dossier\n'

print "A l'arrivée dans %s :" % tvpath
if listMove.__len__() == 0:
    print "Aucun fichier n'a été déplacé"
else:
    print listMove.__len__(),'fichier(s) déplacé(s) :'
    for fichier in listMove:
        print fichier
