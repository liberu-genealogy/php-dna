# Contributing

Contributions are **welcome** and will be fully **credited**. We accept contributions via Pull Requests on [Github](https://github.com/familytree365/php-dna).

## Pull Requests

- **[PSR-4 Coding Standard.]** The easiest way to apply the conventions is to install [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer).
- **Document any change in behaviour.** Make sure the `README.md` and any other relevant documentation are kept up-to-date.
- **Create feature branches.** Don't ask us to pull from your master branch.
- **One pull request per feature.** If you want to do more than one thing, send multiple pull requests.
- **Send coherent history.** Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.
## Using Matchkits

While the repository does not contain a direct reference to "matchkits," users can interact with the repository's functionality through the CLI or programmatically. 

For CLI usage, you can execute scripts within the repository. For example, to run a script from the command line, you might use:

```
php src/Snps/EnsemblRestClient.php --argument1 value1 --argument2 value2
```

For programmatic access, include and utilize classes within your PHP scripts. Here's a simple example:

```php
require_once 'src/Snps/EnsemblRestClient.php';
$ensemblClient = new Dna\Snps\EnsemblRestClient();
$result = $ensemblClient->someMethod();
```

Explore the `src/Snps/` directory for more scripts and classes that can be used from the CLI or programmatically.