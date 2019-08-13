#!/usr/bin/env python
# -*- coding: utf-8 -*-
# Replaces PHP class constructor functions named the same as the class to __construct
# This old style constructor was depreciated in PHP 7.0

import sys, datetime, os

if len(sys.argv) < 2:
	print("arg missing")
	exit()
rootdir = sys.argv[1]

print("RootDir: " + rootdir)

for root, subFolders, ifiles in os.walk(rootdir):
	print "Entering Directory: '"+root
	for ifile in ifiles:
		(name,ext) = os.path.splitext(ifile)
		if ext == ".php": 
			fileName = os.path.join(root, ifile)
			print "Parsing: "+fileName
			with open(fileName, 'r') as f:
				lines = f.readlines()

			inClass = False
			className = ""
			with open(fileName, 'w') as f:
				for line in lines:
					if len(line):
						#print line.strip()
						if inClass:
							if line.startswith("}"):
								inClass = False
							else:
								funcMargin = line.find("function ")
								if funcMargin != -1:
									funcWords = line.strip().split("(")
									#print "Func line:\n"+line+"\n[0]='"+funcWords[0]+"', [1]='"+funcWords[1]+"', className='"+className+"'"
									if funcWords[0] == "function "+ className:
										newLine = line[:funcMargin] + "function __construct(" + "(".join(funcWords[1:])
										print "Update:\n"+line+"To:\n"+newLine
										f.write(newLine+"\n")
										continue
						elif line.strip().startswith("class "):
							className = line[6+line.find("class "):].strip()
							inClass = True
							print "class line:\n"+line+"Class name: "+className
					f.write(line)
			
print "Done"
