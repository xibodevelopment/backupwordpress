module.exports = {
	main: {
		options: {
			mode: 'zip',
			archive: './releases/<%= package.name %>-<%= package.version %>.zip'
		},
		expand: true,
		cwd: 'releases/svn/',
		src: ['**/*']
	},
	dev: {
		options: {
			mode: 'zip',
			archive: './releases/<%= package.name %>-<%= package.version %>-dev.zip'
		},
		expand: true,
		cwd: 'releases/svn/',
		src: ['**/*']
	}
};
