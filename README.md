# MCP Server for WordPress

[![Commit activity](https://img.shields.io/github/commit-activity/m/swissspidy/mcp-wp)](https://github.com/swissspidy/mcp-wp/pulse/monthly)
[![Code Coverage](https://codecov.io/gh/swissspidy/mcp-wp/branch/main/graph/badge.svg)](https://codecov.io/gh/swissspidy/mcp-wp)
[![License](https://img.shields.io/github/license/swissspidy/mcp-wp)](https://github.com/swissspidy/mcp-wp/blob/main/LICENSE)

[Model Context Protocol](https://modelcontextprotocol.io/) server using the WordPress REST API.

Try it by installing and activating the latest nightly build on your own WordPress website:

[![Download latest nightly build](https://img.shields.io/badge/Download%20latest%20nightly-24282D?style=for-the-badge&logo=Files&logoColor=ffffff)](https://swissspidy.github.io/mcp-wp/nightly.zip)

## Usage

Given that no MCP client supports the new Streamable HTTP transport yet, this plugin works best in companion with the [WP-CLI AI command](https://github.com/swissspidy/ai-command).

1. Install plugin
2. Install command
3. Run `wp mcp server add "mysite" "https://example.com/wp-json/mcp/v1/mcp"`
4. Run `wp ai "Greet my friend Pascal"` or so
