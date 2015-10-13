#!/usr/bin/env python

"""Copy info from APs to local machine

Usage:
python getAPconfig.py ip

where ip is optional. If no ip is specified, all APs are scanned.

The user running this script need to have ssh access to the AP with
the users public key on the AP.

Enable ssh:
logon to web-interface, user: root, passw: spoerg NU
Press: Services tab -> Enable SSHd in the Secure Shell section.
Paste the public key from loki: /root/.ssh/id_rsa.pub 
Save
Goto the Administration tab and the Management sub-tab on the Web Interface
Enable "SSH Management" under the section titled "Remote Access" 
Save and reboot router

If there's already ssh access, but no public key, use the following from loki
ssh-copy-id -i /root/.ssh/id_rsa.pub root@ip

Paw - 2015
"""


from __future__ import print_function
import sys
from subprocess import call

ip_list = []
if len(sys.argv) > 1:
    ip_list.append(str(sys.argv[1]))
    
else:
    # Remember that python exclude the last number in the range
    ips=range(27, 38)
    for ip in ips:
        ip_raw = "172.16.2.%d"%(ip)
        ip_list.append(ip_raw)
    # add the 200 AP
    #ip_list.insert(0,"172.16.2.200")

# print(ip_list)

def get_wlan(ip,outfile):
    # get wlan info
    wlan_file = "/tmp/wlan.txt"
    clear_file = "cat /dev/null > %s" %(wlan_file)
    read_file  = "cat %s" %(wlan_file)
    delete_file = "rm %s" %(wlan_file)
    devs = ["ath0","ath0.1","ath1","ath1.1"]
    raw_cmd = ""
    for dev in devs:
        raw_cmd += "iw %s info >> %s;" %(dev,wlan_file)
    remote_cmd = "'(%s; %s %s; %s)'" %(clear_file, raw_cmd, read_file, delete_file)
    cmd = "ssh -oStrictHostKeyChecking=no root@%s %s > %s" %(ip,remote_cmd,outfile)
    call(cmd, shell=True)


def get_nvram(ip,outfile):
    # copy content of nvram
    cmd = "ssh -oStrictHostKeyChecking=no root@%s '(nvram show)' > %s" %(ip,outfile)
    call(cmd, shell=True)

def parse_nvram(ip,nvram_file,wlan_file,summary_file):
    ### process nvram file ###
    # save key=val in dict
    var = {}
    with open(nvram_file) as myfile:
        for line in myfile:
            k, v = line.partition("=")[::2]
            var[k.strip()] = v
    #return var


    vars_to_print  = []
    vars_to_print += ["router_name"]
    # mac addresses: lan, 2.4GHz, Virtual 2.4GHz, 5GHz, Vitual 5GHz
    vars_to_print += ["lan_hwaddr","ath0_hwaddr","ath0.1_hwaddr","ath1_hwaddr","ath1.1_hwaddr"]
    vars_to_print += ["lan_ipaddr","lan_netmask","lan_gateway"]

    ### print summary in one file ###
    f1=open(summary_file, 'a')
    print("\n##############\n",file=f1)
    for key in vars_to_print:
        print(key,var[key],end="",file=f1)

    search_words = ["Interface", "ssid", "channel"]
    for line in open(wlan_file):
        if any(x in line for x in search_words):
            print(line, end="",file=f1)
    ### Print detailed info in another file ###
    # print nvram vars
    vars_to_print.insert(1,"DD_BOARD")
    vars_to_print += ["ath0_radius_ipaddr","ath0_radius_key","ath1_radius_ipaddr","ath1_radius_key"]

    # remove newline(and whitespace)
    detailed_out = "/root/tmp/%s.txt"%(var["router_name"]).rstrip()
    f2=open(detailed_out, 'w')
    for key in vars_to_print:
        print(key,var[key], end="",file=f2)

    # copy content of wlan file to detailed file
    with open(wlan_file) as f:
        content = f.read()
        f2.write(content)




### main loop ###
summary_file = "/root/tmp/ap_summary.txt"
# clear summary_file
open(summary_file, 'w').close()
for ip in ip_list:
    print("Getting info from %s"%(ip))
    wlan_file = "/root/tmp/wlan_raw_%s.txt"%(ip)
    nvram_file = "/root/tmp/nvram_raw_%s.txt"%(ip)
    get_wlan(ip,wlan_file)
    get_nvram(ip,nvram_file)
    parse_nvram(ip,nvram_file,wlan_file,summary_file)

"""
more vars
ath0_ssid
ath0.1_radius_port
ath0_channelbw
ath0.1_ssid
ath0.1_mode
static_route
"""
