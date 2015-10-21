module.exports = {
	pluginClassVersion: {
		src: [
			'classes/class-plugin.php'
		],
		overwrite: true,
		replacements: [ {
			from: /^(\s)+const PLUGIN_VERSION = '.*';$/m,
			to: '$1const PLUGIN_VERSION = \'<%= package.version %>\';'
		} ]
	},
	stableTag: {
		src: [
			'readme/readme-header.txt'
		],
		overwrite: true,
		replacements: [ {
			from: /^Stable tag: .*$/m,
			to: 'Stable tag: <%= package.version %>'
		} ]
	},

	pluginVersion: {
		src: [
			'backupwordpress.php'
		],
		overwrite: true,
		replacements: [ {
			from: /^Version: .*$/m,
			to: 'Version: <%= package.version %>'
		} ]
	},
	faq          : {
		src         : [
			'admin/faq.php'
		],
		dest        : 'readme/faq.txt',
		replacements: [
			{
				from: /.*<p>' \. __\( '(.*)', '\w+' \).*/mg,
				to  : '$1'
			},
			{
				from: '<?php',
				to  : ''
			},
			{
				from: /\\'/g,
				to:   '\''
			},
			{
				from: /.*<strong>.*__\( '(.*)', '\w+' \).*<\/strong>.*/g,
				to: '**$1**'
			},
			{
				from: /'(?:<ul>)?<li>' \. .*__\( '(.*)', '.*' \) .* '<\/li>(?:<\/ul>)?' \./g,
				to: '* $1'
			},
			{
				from: /<\/?code>/g,
				to: '`'
			},
			{
				from: /<a href="(.*)" title="(.*)" target="_blank">(.*)<\/a>/g,
				to: '[$3]($1 "$2")'
			}
		]
	}
};
