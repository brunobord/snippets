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

dlpath = '' # Your download folder	# Dossier de départ
tvpath = '' # Your "Shows" folder	# Dossier d'arrivée

if len(dlpath) == 0 or len(tvpath) == 0:
    print "Pour automatiser, veuillez entrer les dossiers de départ et d'arrivée dans le script (vars 'dlpath' & 'tvpath')\n"
    dlpath = raw_input("Entrer le dossier de départ (ex. /Downloads) : ")
    tvpath = raw_input("Entrer le dossier d'arrivée (ex. /Shows) : ")

if platform.system() == 'Windows':		
    os.path.normcase(dlpath)
    os.path.normcase(tvpath)
        
    if dlpath[-1:] != '\\':
        dlpath += '\\'
        
    if tvpath[-1:] != '\\':
        tvpath += '\\'
else:
    if dlpath[-1:] != '/':
        dlpath += '/'
        
    if tvpath[-1:] != '/':
        tvpath += '/'

# Create listFile containing the show name, show season (according to the file), and the filename
# Création de listFile contenant le nom de la série, la saison du fichier, et le filename
listFile = []
for file in os.listdir(dlpath):
    nick = str(file).lower()
    if '.s0' in nick:
        listPart = list(nick.partition('.s0'))
        saison = listPart[2][:1]
    elif '.s1' in nick:
        listPart = list(nick.partition('.s1'))
        saison = '1'+listPart[2][:1]
    if '.s0' in nick or '.s1' in nick:
        nick = listPart[0]
        name = nick.replace('.', ' ')
        name = string.capwords(name)
        listFile.append([name, saison, file])

# Move the files at the right place reading listFile
# On déplace les fichiers en parcourant listFile
listMove = []
listDir = os.listdir(tvpath)
for content in listFile:
# content[0] is the the show name (used to create the "/<show name>" folder)
# content[1] is the show season (according to the file)
# content[2] is the filename
# - - - 
# content[0] est le nom de la série (utilisé pour le nom des dossiers)
# content[1] est la saison
# content[2] est le nom du fichier
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
# Affichage du résultat
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
