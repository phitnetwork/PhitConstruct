#!/bin/bash

find .git/objects/ -type f -empty -delete
git fetch -p
git fsck --full
