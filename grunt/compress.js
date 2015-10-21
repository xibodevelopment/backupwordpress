module.exports = {
	main: {
		options: {
			archive: 'releases/<%= package.name %>-<%= package.version %>.zip'
		},
		expand: true,
		cwd: 'releases/svn/trunk/',
		src: ['**/*'],
	},
	dev: {
		options: {
			archive: 'releases/<%= package.name %>-<%= package.version %>-dev.zip'
		},
		expand: true,
		cwd: 'releases/svn/trunk/',
		src: ['**/*'],
	}
};
