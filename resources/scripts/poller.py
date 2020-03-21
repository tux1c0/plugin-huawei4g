#!/usr/bin/env python3
from huawei_lte_api.Client import Client
from huawei_lte_api.AuthorizedConnection import AuthorizedConnection
from huawei_lte_api.Connection import Connection
import json
import sys


if len(sys.argv) == 4:
	ip = sys.argv[1]
	login = sys.argv[2]
	pwd = sys.argv[3]
	list = []

	try:
		connection = AuthorizedConnection('http://'+login+':'+pwd+'@'+ip)
		client = Client(connection)

		try:
			list.append(json.dumps(client.user.state_login()))
		except:
			list.append({"state_login()":"Not supported"})

		try:
			list.append(json.dumps(client.monitoring.traffic_statistics()))
		except:
			list.append({"traffic_statistics()":"Not supported"})

		try:
			list.append(json.dumps(client.net.current_plmn()))
		except:
			list.append({"current_plmn":"Not supported"})

		try:
			list.append(json.dumps(client.device.basic_information()))
		except:
			list.append({"basic_information()":"Not supported"})

		try:
			list.append(json.dumps(client.device.information()))
		except:
			list.append({"information()":"Not supported"})

		try:
			list.append(json.dumps(client.device.signal()))
		except:
			list.append({"signal()":"Not supported"})

		try:
			list.append(json.dumps(client.sms.get_sms_list()))
		except:
			list.append({"get_sms_list()":"Not supported"})

		client.user.logout()

	except:
		list.append(sys.exc_info())


	print(list)
else:
	print("No parameter has been included")

