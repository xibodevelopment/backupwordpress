module.exports = {
	build: {
		command: 'mkdir -p releases/svn && rsync -avzrR --exclude-from \'grunt/excludes\' . releases/svn/trunk'
	}
};
