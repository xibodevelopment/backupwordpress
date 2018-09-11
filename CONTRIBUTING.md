# Contributing to BackUpWordPress

Thanks for stopping by, It's really great that you're here. BackUpWordPress thrives on the contributions of [many](https://github.com/xibodevelopment/backupwordpress/graphs/contributors) and there is always more to do.

There are four main ways you can contribute currently:

- Developing
- Testing
- Translating
- Security

## Developing

Develop your changes in a feature branch and send a pull request to BackUpWordPress' `master` branch for review.

Assign the Pull Request to the Lead Developer: Paul de Wouters, [@pdewouters](https://github.com/pdewouters).

You can use Grunt to generate a built copy of the plugin to test with:

```
npm install
grunt copy:build
```

We try to follow the [WordPress Core Coding Standards](http://codex.wordpress.org/WordPress_Coding_Standards).

## Testing

We're trying to solve a hard problem. BackUpWordPress aims to work reliably across 24%+ of the internet that WordPress is installed on. It needs to support the multitude of server setups, plugin and theme configurations and types of sites that WordPress itself can handle. In order to reach this goal we need to be continuously testing our changes to ensure they improve the things we intended to improve and avoid breaking things we didn't intend to break.

We rely on a few different kinds of tests:

- Unit tests help us ensure that small, atomic pieces of code (generally individual functions or methods) do what they should under a variety of input conditions. We use Travis to run them automatically against all new Pull Requests. If you submit a code change you should try to also submit some unit tests to cover the changes you are making. We're [working on documenting how to setup and run our unit tests locally](https://github.com/xibodevelopment/backupwordpress/issues/837).
- Integration tests are something we need, we don't currently have them.
- Manual testing of different hosting environments. If you have access to a specific hosting environment, you can help out hugely by installing BackUpWordPress and testing if it works correctly. We're working on a [host support matrix](https://github.com/xibodevelopment/backupwordpress/issues/838) that you can contribute your findings too but for now please just open a Github issue if you run into a problem.

## Translating

We want BackUpWordPress to be available in as many languages as possible.

All translations are managed here: https://translate.wordpress.org/projects/wp-plugins/backupwordpress/dev please contribute there rather than by submitting new translation files here. You'll need a WordPress.org account.

## Security

We take the security of BackUpWordPress extremely seriously. If you think you've found a security issue with the plugin (whether information disclosure, privilege escalation, or another issue), we'd appreciate responsible disclosure as soon as possible.

To report a security issue, you can email support@xibomarketing.com. We will attempt to give an initial response to security issues within 48 hours at most, however keep in mind that the team is distributed across various timezones, and delays may occur as we discuss internally.

(Please note: For testing, you should install a copy of the project and WordPress on your own server. Do not test on servers you do not own.)

Thank you for contributing!
