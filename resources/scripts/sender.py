#!/usr/bin/env python3
from huawei_lte_api.Client import Client
from huawei_lte_api.AuthorizedConnection import AuthorizedConnection
from huawei_lte_api.Connection import Connection
import json
import sys

if len(sys.argv) == 6:
	ip = sys.argv[1]
	login = sys.argv[2]
	pwd = sys.argv[3]
	phone = sys.argv[4]
	message = sys.argv[5]
	list = []
	
	try:
		connection = AuthorizedConnection('http://'+login+':'+pwd+'@'+ip)
		client = Client(connection)

		list.append(json.dumps(client.sms.send_sms([phone], message)))
		client.user.logout()

	except:
		list.append(sys.exc_info())

	print(list)
else:
	print("No parameter has been included")
