# -*- coding: utf-8 -*-
"""
# Name: MoveEpisodes
# What: Move and sort your episodes from your download folder to the right folder (eg. "/<show name>/Season x")
# Why: 'Cause it's better when it's automatic
"""

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


# Create listFile containing the show name, show season (according to the file), and the filename
listFile = []
for f in os.listdir(dlpath):
    nick = f.lower()
    if '.s0' in nick:
        listPart = nick.partition('.s0')
        saison = listPart[2][:1]
    elif '.s1' in nick:
        listPart = nick.partition('.s1')
        saison = '1'+listPart[2][:1]
    if '.s0' in nick or '.s1' in nick:
        nick = listPart[0]
        name = nick.replace('.', ' ')
        name = string.capwords(name)
        listFile.append([name, saison, f])

# Move the files at the right place reading listFile
listMove = []
listDir = os.listdir(tvpath)
for name, saison, filename in listFile:
# name / content[0] is the the show name (used to create the "/<show name>" folder)
# saison / content[1] is the show season (according to the file)
# filename / content[2] is the filename

    # here you discover the magic of os.path...
    if name in listDir:
        listSubDir = os.listdir(os.path.join(tvpath, name))
        if not 'Saison %s' % saison in listSubDir:
            os.makedirs(os.path.join(tvpath, name, 'Saison %s' % saison))
    else:
        os.makedirs(os.path.join(tvpath, name))
        os.makedirs(os.path.join(tvpath, name, 'Saison %s' % saison))

    shutil.move(os.path.join(dlpath, filename), os.path.join(tvpath, name, 'Saison %s' % saison, filename))
    listMove.append(filename)

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
