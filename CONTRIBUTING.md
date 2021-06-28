# Contributing to the Microsoft Graph PHP SDK Core
Thanks for considering making a contribution! Read over our guidelines and we will do our best to see your PRs merged successfully.

**NOTE**: A signed a contribution license agreement is required for all contributions and is checked automatically on new pull requests. You will be asked to read and sign the agreement https://cla.microsoft.com/ after submitting a request to this repository.

There are a few different recommended paths to get contributions into the released version of this library.

## File issues
The best way to get started with a contribution is to start a dialog with us. Sometimes features will be under development or out of scope for this library and it's best to check before starting work on contribution, especially for large work items.

## Pull requests
If you are making documentation changes, feel free to submit a pull request against the **master** branch. All other pull requests should be submitted against the **dev** branch or a specific feature branch. The master branch is intended to represent the code released in the most-recent composer package.

When a new package is about to be released, changes in dev will be merged into master. The package will be generated from master.

Some things to note about this project:

### How the library is built
The PHP SDK has a handwritten set of core files.

 
However, this is evaluated on a case-by-case basis. If the library is missing v1.0 Graph functionality that you wish to utilize, please [file an issue](https://github.com/microsoftgraph/msgraph-sdk-php-core/issues).

We do our best to prevent breaking changes from being introduced into the library during this process. If you find a breaking change, please file an issue and we will work to get this resolved ASAP.


