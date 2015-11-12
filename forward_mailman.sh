#!/bin/bash
# Læs en mail fra std-in og tilføj den til mailman. Listen skal med som
# argument når scriptet kaldes.
# tilføj følgende til visudo for at køre mmarch uden at være root.
# nobody ALL = (ALL) NOPASSWD:/usr/sbin/mmarch
# Husk at sætte de rigtige rettigheder for denne fil:
# chown nobody /root/scripts/forward_mailman.sh
#
# Lav et symbolsk link til /etc/postfix/
# ln -s /root/scripts/forward_mailman.sh /etc/postfix/forward_mailman.sh
# Test med (hvor pawse også skal tilføjes visudo under test)
# su - pawse /etc/postfix/forward_mailman.sh test < /var/mail/arkiv/nettest
# Paw - 2015

[ -z "$1" ] && { printf "Usage: ${0##*/} <liste til mailman>\n"; exit; }

tmp=`tempfile`||exit

# This will read from STDIN
cat>$tmp

# run cmd. Remember to add nobody to visudo.
sudo /usr/sbin/mmarch "$1" $tmp
echo $1
rm $tmp
