module.exports = {
	options: {
		'boss': true,
		'curly': true,
		'eqeqeq': true,
		'eqnull': true,
		'es3': true,
		'expr': true,
		'immed': true,
		'noarg': true,

		'onevar': true,

		'trailing': true,
		'undef': true,
		'unused': true,

		'browser': true,
		'devel': true,

		'globals': {
			'_': true,
			'Backbone': true,
			'jQuery': true,
			'wp': true,
			'hmbkp': true,
			'ajaxurl': true
		}
	},
	all: ['Gruntfile.js', 'assets/hmbkp.js']
};
