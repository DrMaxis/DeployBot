# Contributing

Thanks for considering a contribution.

## Issues

- **Bugs:** use the Bug report template. Include a minimal repro + PHP / Laravel / DeployBot versions.
- **Features:** use the Feature request template. Describe the use case before the API.
- **Security:** see [SECURITY.md](SECURITY.md) — do not file public issues.
- **Questions:** use [GitHub Discussions](https://github.com/DrMaxis/DeployBot/discussions).

## Pull requests

1. Open an issue first for anything more than a typo.
2. Fork + branch from `develop`.
3. Run the quality bar locally before pushing:

   ```bash
   composer install
   composer test
   composer analyze
   composer check
   ```

4. Add tests for new features. Add a regression test for bug fixes.
5. Update `CHANGELOG.md` under `[Unreleased]`.
6. Open the PR against `develop`. `main` is reserved for tagged releases.

## Code style

Standard Laravel conventions. Pint enforces formatting (`composer format`). Larastan enforces analysis (`composer analyze`).

## License

By contributing you agree your contributions are licensed under [MIT](LICENSE).
