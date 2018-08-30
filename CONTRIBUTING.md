# What is this file?

> This document is a _living document_ -- that is, it will constantly be changed as the project evolves and develops. The date below will be updated whenever new changes have been made to this document. The goal of this document is to give contributors and potential contributors a quick overview of the project and how they can contribute. Be sure to check here before branching, committing or submitting pull requests.

### Last updated 11/4/2015

#### Current active development branch: ```develop```

### Changelog

* 11/4/2015 - belated update of current active development branch
* 9/21/2015 - initial draft

## Philosophy

The main goal of Simple Page Builder is to make the job of a _content editor_ easier by removing the decisions required of many other page builders and solutions like Advanced Custom Fields. These options are great in terms of giving editors and admins control over their page, but often require time and experience with HTML and/or design to produce pages that look good. We are theme and plugin developers, we should be able to provide a solution that always looks good, no matter what, and does not require the user to come up with solutions to design problems.

The Simple Page Builder is targeted at plugin and theme developers who want to provide their users with a simple way to add premade content blocks to posts, pages and custom post types with an emphasis on the KISS principle as it applies to end users (content editors) who probably don't know anything about web design, graphic design, or development. Simple Page Builder can be thought of as a replacement to the WordPress widget system, which has frequently been applied to widgetized areas outside of the original context of "sidebars" and provides a poor UI when using many widgetized areas for different pages or areas of a site.

## Development

SPB2 uses the [Git Flow](http://nvie.com/posts/a-successful-git-branching-model/) branching model for development. If you're unfamiliar with Git Flow, you should [read that post now](http://nvie.com/posts/a-successful-git-branching-model/) to understand the theory before committing. There are two major branch streams and two branch types (the /release branches are not used) that Page Builder uses.

**```master```** This holds the latest **stable** release.

**```develop```** Development for the next release is merged here. This isn't a bleeding-edge, broken firehose. Only stable, tested patches get merged into the ```develop``` branch so it can be used as a base for feature branches. Only pull requests submitted to the ```develop``` branch will be accepted. No pull requests for ```master``` will ever be merged.

**```feature/{new_branch}```** All new features for Page Builder should be in feature branches. When creating a feature branch, prefix your branch name with ```feature/```, e.g. ```feature/included_template_parts```.

**```hotfix/{new_hotfix}```** Any bug fixes should be in hotfix branches. These need to merge into both ```master``` and ```develop```.

If an issue is being addressed by your branch or commit, be sure to add the ticket number in the commit notes so your commit gets tracked within the ticket.
