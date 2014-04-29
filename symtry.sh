#!/bin/sh
. /etc/profile.d/modules.sh
module load cctbx
./symtry.py $@