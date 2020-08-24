#!/usr/bin/env python3
from huawei_lte_api.Client import Client
from huawei_lte_api.AuthorizedConnection import AuthorizedConnection
from huawei_lte_api.Connection import Connection
import huawei_lte_api
import json
import sys

def Clean_JSON(wrongJSON):
	#while True:
		#try:
			#result = json.loads(wrongJSON)   # try to parse...
			#break                    # parsing worked -> exit loop
		#except Exception as e:
			# "Expecting , delimiter: line 34 column 54 (char 1158)"
			# position of unexpected character after '"'
			#unexp = int(re.findall(r'\(char (\d+)\)', str(e))[0])
			# position of unescaped '"' before that
			#unesc = wrongJSON.rfind(r'"', 0, unexp)
			#wrongJSON = s[:unesc] + r'\"' + s[unesc+1:]
			# position of correspondig closing '"' (+2 for inserted '\')
			#closg = wrongJSON.find(r'"', unesc + 2)
			#wrongJSON = s[:closg] + r'\"' + s[closg+1:]
	return wrongJSON.replace('\"','')

if len(sys.argv) == 4:
	ip = sys.argv[1]
	login = sys.argv[2]
	pwd = sys.argv[3]
	list = []

	list.append('{"huawei_lte_api": "'+huawei_lte_api.__version__+'"}')
	
	try:
		connection = AuthorizedConnection('http://'+login+':'+pwd+'@'+ip)
		client = Client(connection)

		try:
			list.append(json.dumps(client.user.state_login()))
		except:
			list.append('{"state_login()": "Not supported"}')

		try:
			list.append(json.dumps(client.sms.sms_count()))
		except:
			list.append('{"sms_count()": "Not supported"}')
			
		try:
			list.append(Clean_JSON(json.dumps(client.sms.get_sms_list())))
		except:
			list.append('{"get_sms_list()": "Not supported"}')

		client.user.logout()

	except:
		list.append(sys.exc_info())

	print(list)
else:
	print("No parameter has been included")

