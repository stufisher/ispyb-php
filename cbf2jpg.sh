#!/bin/sh
. /etc/profile.d/modules.sh
module load mosflm

ipmosflm << eof
DETECTOR PILATUS
XGUI ON
DIRECTORY $1
TEMPLATE $2
IMAGE $3
GO
CREATE_IMAGE ZOOM -1 BINARY TRUE FILENAME $4
RETURN
EXIT
eof

rm fort.8