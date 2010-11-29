#!/usr/bin/python
# -*- coding: utf-8 -*-
# What:Just a basic (beginner) test to create a wxPython window

import wx

class Fen(wx.Frame):
	def __init__(self, parent, id, titre):
		wx.Frame.__init__(self, parent, id, titre)
		
		boite = wx.BoxSizer(wx.VERTICAL)
		texte = wx.StaticText(self, -1, "Texte de test, jolie phrase ...")
		boite.Add(texte, flag=wx.ALIGN_CENTER | wx.ALL, border=20)
		boite2 = wx.BoxSizer(wx.HORIZONTAL)
		bouton = wx.Button(self, wx.ID_OK)
		boite2.Add(bouton)
		boite.Add(boite2, flag=wx.ALIGN_CENTER | wx.BOTTOM, border=20)
		
		self.SetSizerAndFit(boite)
		self.Centre()	
			
class Appli(wx.App):
	def OnInit(self):
		fenetre = Fen(None, -1, "wxPython, c'est génial")
		fenetre.Show()
		return True
		
app = Appli()
app.MainLoop()
