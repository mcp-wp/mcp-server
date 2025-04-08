# MCP Server for WordPress

[![Commit activity](https://img.shields.io/github/commit-activity/m/mcp-wp/mcp-server)](https://github.com/mcp-wp/mcp-server/pulse/monthly)
[![Code Coverage](https://codecov.io/gh/mcp-wp/mcp-server/branch/main/graph/badge.svg)](https://codecov.io/gh/mcp-wp/mcp-server)
[![License](https://img.shields.io/github/license/mcp-wp/mcp-server)](https://github.com/mcp-wp/mcp-server/blob/main/LICENSE)

[Model Context Protocol](https://modelcontextprotocol.io/) server using the WordPress REST API.

Try it by installing and activating the latest nightly build on your own WordPress website:

[![Download latest nightly build](https://img.shields.io/badge/Download%20latest%20nightly-24282D?style=for-the-badge&logo=Files&logoColor=ffffff)](https://mcp-wp.github.io/mcp-server/mcp.zip)

## Description

This WordPress plugin aims to implement the new [Streamable HTTP transport](https://spec.modelcontextprotocol.io/specification/2025-03-26/basic/transports/#streamable-http), as described in the latest MCP specification.

Under the hood it uses the [`logiscape/mcp-sdk-php`](https://github.com/logiscape/mcp-sdk-php) package to set up a fully functioning MCP server. Then, this functionality is exposed through a new `wp-json/mcp/v1/mcp` REST API route in WordPress.

Note: the Streamable HTTP transport is not fully implemented yet and there are no tests. So it might not 100% work as expected.

## Usage

Given that no other MCP client supports the new Streamable HTTP transport yet, this plugin works best in companion with the [WP-CLI AI command](https://github.com/mcp-wp/ai-command).

1. Run `wp plugin install --activate https://github.com/mcp-wp/mcp-server/archive/refs/heads/main.zip`
2. Run `wp plugin install --activate ai-services`
3. Run `wp package install mcp-wp/ai-command:dev-main`
4. Run `wp mcp server add "mysite" "https://example.com/wp-json/mcp/v1/mcp"`
5. Run `wp ai "Greet my friend Pascal"` or so

Note: The WP-CLI command also works on a local WordPress installation without this plugin.
