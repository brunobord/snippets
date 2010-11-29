#!/usr/bin/python
# -*- coding: utf-8 -*-
# What:Just a basic (beginner) test to create a Tkinter window

import Tkinter

class Fen(Tkinter.Frame):
	def __init__(self, titre):
		Tkinter.Frame.__init__(self)
		self.master.title(titre)
		
		texte = Tkinter.Label(self, text="Texte de test, jolie phrase...")
		texte.pack(pady=10)
		boiteh = Tkinter.Frame(self)
		
		reponse = Tkinter.Entry(boiteh)
		reponse.pack(side="right")
		boiteh.pack(pady=5)
		
		bouton = Tkinter.Button(self, text="Ok")
		bouton.pack(ipadx=20, pady=5)
		
		self.pack()
		
app = Fen("Ma fenetre Tkinter")
app.mainloop()
