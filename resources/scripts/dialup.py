#!/usr/bin/env python3
from huawei_lte_api.Client import Client
from huawei_lte_api.AuthorizedConnection import AuthorizedConnection
from huawei_lte_api.Connection import Connection
import huawei_lte_api
import json
import sys


if len(sys.argv) == 5:
	ip = sys.argv[1]
	login = sys.argv[2]
	pwd = sys.argv[3]
	dataswitch = sys.argv[4]
	list = []

	list.append('{"huawei_lte_api": "'+huawei_lte_api.__version__+'"}')
	
	try:
		connection = AuthorizedConnection('http://'+login+':'+pwd+'@'+ip)
		client = Client(connection)

		try:
			list.append(json.dumps(client.dial_up.set_mobile_dataswitch(dataswitch)))
		except:
			list.append('{"set_mobile_dataswitch()": "Not supported"}')

		client.user.logout()

	except:
		list.append(sys.exc_info())

	print(list)
else:
	print("No parameter has been included")

