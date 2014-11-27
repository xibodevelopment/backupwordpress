PLUGIN_DIR=$(pwd)
PHPCS_DIR=/tmp/phpcs
PHPCS_GITHUB_SRC=squizlabs/PHP_CodeSniffer
PHPCS_GIT_TREE=master
PHPCS_IGNORE='tests/*,vendor/*'
WPCS_DIR=/tmp/wpcs
WPCS_GITHUB_SRC=WordPress-Coding-Standards/WordPress-Coding-Standards
WPCS_GIT_TREE=master
WPCS_STANDARD=$(if [ -e phpcs.ruleset.xml ]; then echo phpcs.ruleset.xml; else echo WordPress-Core; fi)

 mkdir -p $PHPCS_DIR && curl -L https://github.com/$PHPCS_GITHUB_SRC/archive/$PHPCS_GIT_TREE.tar.gz | tar xvz --strip-components=1 -C $PHPCS_DIR
mkdir -p $WPCS_DIR && curl -L https://github.com/$WPCS_GITHUB_SRC/archive/$WPCS_GIT_TREE.tar.gz | tar xvz --strip-components=1 -C $WPCS_DIR
$PHPCS_DIR/scripts/phpcs --config-set installed_paths $WPCS_DIR
