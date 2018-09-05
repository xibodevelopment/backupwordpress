# release.sh
#
# Takes a tag to release, and syncs it to WordPress.org

TAG=3.6.4.1
PLUGIN="backupwordpress-new"
PLUGINDIR=$(pwd)
TMPDIR=$(pwd)/tmp/

# Fail on any error
set -e

if [[ $VERSION == "v*" ]]; then
	# Starts with an extra "v", strip for the version
	VERSION=${TAG:1}
else
	VERSION="$TAG"
fi

echo "Version: $VERSION ."

if [ -d "${TMPDIR}" ]; then
	# Wipe it clean
	rm -rf "${TMPDIR}"
	echo "Remove TMP"
fi

# Ensure the directory exists first
mkdir -p "${TMPDIR}"

echo "Make TMP: ${TMPDIR}"

# Make a Copy of all the files in the folder
rsync -av --progress "${PWD}" "${TMPDIR}" --exclude="${TMPDIR}" --exclude="${TMPDIR}node_modules/" --exclude="${TMPDIR}.git/"
if [[ $? -ne 0 ]]; then
	remove_tmp
	echo "rsync $PLUGINDIR Failed. Aborting."
	exit 1
fi

# Switch to build dir
cd "${TMPDIR}"

# change folder name to TAG ID
mv ${PLUGIN} ${TAG}

# THen CD into folder
cd ${TAG}

ls -lh

# Run build tasks
sed -e "s/{{TAG}}/$VERSION/g" < "$PLUGINDIR/bin/readme.txt" > readme.txt

# Remove special files
rm .gitignore
rm .gitmodules
rm .bowerrc
rm .travis.yml
rm .jshintrc
rm -rf .git
rm -rf .github
rm CONTRIBUTING.md
rm changelog.txt
rm .scrutinizer.yml
rm phpunit.xml
rm bower.json
rm Gruntfile.js
rm package.json
rm composer.json
rm composer.lock
rm -rf bin
rm -rf grunt
rm -rf tmp
rm -rf readme
rm -rf tests
rm -rf node_modules