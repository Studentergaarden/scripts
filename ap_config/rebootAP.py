#!/usr/bin/env python

"""Reboot AP in order to make sure all settings made by the
web-interfaced are effectuated.

Usage:
python rebootAP.py ip

where ip is optional. If no ip is specified, all APs are rebooted.

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
    ip_list.insert(0,"172.16.2.200")

# print(ip_list)


def reboot(ip):
    cmd = "ssh -oStrictHostKeyChecking=no root@%s '(reboot)'" %(ip)
    call(cmd, shell=True)

### main loop ###
for ip in ip_list:
    print("Rebooting %s"%(ip))
    reboot(ip)

