#!/usr/bin/python
# -*- coding: utf-8 -*-
# What:Just a basic (beginner) test to create a PyQt window

from PyQt4 import QtGui
import sys

class Fen(QtGui.QDialog):
	def __init__(self, titre):
		QtGui.QDialog.__init__(self)
		self.setWindowTitle(titre)
		
		boite = QtGui.QVBoxLayout()
		texte = QtGui.QLabel("Texte de test, jolie phrase...")
		boite.addWidget(texte)
		
		boite2 = QtGui.QHBoxLayout()
		reponse = QtGui.QLineEdit()
		boite2.addWidget(reponse)
		boite.addLayout(boite2)
		bouton = QtGui.QPushButton("Ok")
		boite.addWidget(bouton)
		
		self.setLayout(boite)
		
app = QtGui.QApplication(sys.argv)
fenetre = Fen("Ma fenetre PyQt")
fenetre.exec_()
