#!/usr/bin/env bash
# Fetch files from repository, updating local versions


# Binaries
GIT=$(which git)

# Fix /code and .ssh permissions before updating
sudo chmod -R ug+rw /code
sudo chmod 755 ~/.ssh
sudo chmod 644 ~/.ssh/*
sudo chmod 600 ~/.ssh/id_rsa

# Folders definition
TMP_FOLDER=~/docker-behat
LOCAL_FOLDER=/code

# Remote files and folders to delete if local exists
REMOTE_BLACKLIST=Docker/scripts/remote.blacklist

# Local files and folders to delete
LOCAL_BLACKLIST=Docker/scripts/local.blacklist

# Create tmp folder if it doesn't exist
[ -d $TMP_FOLDER ] || mkdir -p $TMP_FOLDER

"${GIT}" clone --branch latest ssh://git@bitbucket.org/ciandt_it/docker-behat.git $TMP_FOLDER

cd $TMP_FOLDER

# For files in the REMOTE_BLACKLIST
# if file/folder exists locally, delete it from tmp dir so it won't overwrite the local version
# if it doesn't, keep it on tmp dir to be copied. 
cat $TMP_FOLDER/$REMOTE_BLACKLIST | while read f
do
  [ -e $LOCAL_FOLDER/$f ] && rm -Rf "$TMP_FOLDER/$f" || echo "No $f found locally. Will get the remote version"
done

# For files in the LOCAL_BLACKLIST
# delete local file/folder
cat $TMP_FOLDER/$LOCAL_BLACKLIST | while read f
do
  rm -Rf "$LOCAL_FOLDER/$f"
done

# Copy remote files from the tmp dir to the local folder, overwriting files
# that exist and preserving files ownership and permissions
cp --no-preserve=mode,ownership -TR $TMP_FOLDER/ $LOCAL_FOLDER/ 

# Delete tmp folder  
rm -Rf $TMP_FOLDER/
