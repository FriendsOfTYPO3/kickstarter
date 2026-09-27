# 🚀 Contributing to TYPO3 Extension Kickstarter

## 👋 Welcome!

Thank you for your interest in contributing to this project! We welcome issues, suggestions, and contributions that help improve the project for everyone.

## 🐞 Reporting Issues

If you find a bug or have a feature request, please create an issue on our [GitHub repository](https://github.com/FriendsOfTYPO3/kickstarter/issues) with the following information:

- A clear, descriptive title
- A detailed description of the issue or feature request
- Steps to reproduce the issue (for bugs)
- Expected behavior and actual behavior
- TYPO3 version and kickstarter version
- Any relevant screenshots or error messages

## 🧑‍💻 Contributing Code

1. **Fork the repository** - Create your own fork of the project
2. **Create a branch** - Create a branch for your changes with a descriptive name
3. **Make your changes** - Implement your bug fix or feature
4. **Test your changes** - Ensure your changes don't break existing functionality
5. **Submit a pull request** - Open a pull request with a clear description of your changes

## 💬 Before You Start a Pull Request

Before putting significant time into a pull request, **please [open an issue](https://github.com/FriendsOfTYPO3/kickstarter/issues)** or reach out to the maintainer. This helps ensure that:

- Your proposal aligns with the project’s goals
- No one else is working on the same change
- You don’t spend time on something that might not be accepted

## 🧹 Coding Standards

This project follows TYPO3 coding standards. Please ensure your code adheres to these standards:

- Follow [PSR-2](https://www.php-fig.org/psr/psr-2/) coding style
- Use [TYPO3's coding guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/Index.html)
- Write clear, descriptive commit messages
- Include appropriate documentation for new features

## 📚 Documentation

Improvements to documentation are always welcome. If you find something unclear or missing in the documentation, please feel free to submit a pull request with your improvements.

## 🛠️ Development Setup

To set up a development environment for this extension:

1. Clone the repository
2. Install dependencies and set up the environment using:

```bash
Build/Scripts/runTests.sh -s composerUpdate
```

## ✅ Testing

To run the current test suites, use the following commands:

### 🧽 For `cgl`:

```bash
Build/Scripts/runTests.sh -s cgl
```

### 🧐 For `phpstan`:

```bash
Build/Scripts/runTests.sh -s phpstan
```

### 🧹 For `rector`:

Before running the tests, make sure to update rector:

```bash
Build/Scripts/runTests.sh -s composerUpdateRector
```

Then you can run rector:

```bash
Build/Scripts/runTests.sh -s rector
```

> Note: Unit and functional tests will be introduced in a future update.

## 🤝 Code of Conduct

We expect all contributors to be respectful and considerate of others. We aim to foster an inclusive and welcoming community where everyone feels comfortable contributing.

## 📄 License

By contributing to this project, you agree that your contributions will be licensed under the same license as the project, (see [LICENSE.txt](LICENSE.txt)).

## ❓ Questions?

You can reach us in the TYPO3 Slack channel `#extension-builder`. If you're not yet on TYPO3 Slack, see the guide here: [How to use Slack in the TYPO3 community](https://typo3.org/community/meet/how-to-use-slack-in-the-typo3-community)

Use the [issue tracker](https://github.com/FriendsOfTYPO3/kickstarter/issues) for bug reports, feature requests, and coordination.

Thank you for your contributions!

## 🙌 Thank You!

We appreciate your contributions—whether it's filing an issue, suggesting an improvement, or submitting a pull request. Let's build something great together!
