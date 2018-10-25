from __future__ import division
import re
import sys
import os

readfiles = os.popen('ls HTML_files')
files = readfiles.readlines()
i=0
for file in files:
	content = os.popen('java -jar tika-app-1.17.jar -m HTML_files/' + file.strip())
	lines = content.readlines()
	for line in lines:
		if line.startswith('title:') or line.startswith('description:'):
			line = line.replace('|','')
			line = line.replace('\'','')
			line = line.replace('\"','')
			line = line.replace('Fox News','')
			line = line.replace(':','')
			line = line.replace(';','')
			line = line.replace('title','')
			line = line.replace('description','')
			print 'echo ' + line.strip() + ' >> big.txt'
			os.system('echo ' + line.strip() + ' >> big.txt')

	i += 1
	print 'NUMBER: ' + str(i)
	print 'RATE OF PROGRESS: ' + str(i/185.62) + '%'

