# Fluent MCP Agent

An intelligent AI agent for WordPress that integrates with the Model Context Protocol (MCP) to provide chat capabilities and execute WordPress abilities through AI assistants. Supports multiple AI providers including Ollama (local), OpenAI (ChatGPT), and Anthropic (Claude).

## 🎯 Overview

Fluent MCP Agent is a WordPress plugin that brings AI-powered chat capabilities directly into your WordPress admin panel. It acts as a bridge between AI models and WordPress's Abilities API, allowing AI assistants to discover and execute WordPress capabilities as tools.

**Key Features:**
- 🤖 Multi-provider AI support (Ollama, OpenAI, Anthropic)
- 💬 Interactive chat interface in WordPress admin
- 🔧 Automatic discovery of WordPress abilities via MCP
- 🔄 Streaming responses for real-time conversations
- 🛠️ Tool/function calling support for AI models
- 🌐 Offline-first support with Ollama
- 🔐 Secure authentication and permission handling

## 📋 Requirements

- **WordPress**: 6.9+ (for Abilities API support)
- **PHP**: 7.4 or higher
- **Composer**: For dependency management
- **Node.js & npm**: For frontend development (optional, for building assets)

### Optional Dependencies

- **Ollama**: For local AI model support ([Installation Guide](https://ollama.ai))
- **OpenAI API Key**: For ChatGPT integration
- **Anthropic API Key**: For Claude integration

## 🚀 Installation

### 1. Install via Composer

```bash
cd wp-content/plugins/fluent-mcp-agent
composer install
```

### 2. Build Frontend Assets (Optional)

If you want to modify the frontend or build from source:

```bash
cd my-assistant-app
npm install
npm run build
```

### 3. Activate the Plugin

1. Go to **WordPress Admin → Plugins**
2. Find **Fluent MCP Agent**
3. Click **Activate**

## ⚙️ Configuration

### Initial Setup

1. Navigate to **Fluent MCP Agent → Settings** in WordPress admin
2. Enable at least one AI provider
3. Configure provider-specific settings
4. Set a default provider

### Provider Configuration

#### Ollama (Local AI)

**Best for**: Privacy-focused, offline-first AI interactions

1. **Install Ollama**: Download from [ollama.ai](https://ollama.ai)
2. **Start Ollama**: Run `ollama serve` in your terminal
3. **Pull a model**: `ollama pull llama2` (or any model you prefer)
4. **Enable Ollama** in plugin settings
5. **Configure URL**: Default is `http://localhost:11434/api/chat`

**Available Models**:
- `llama2`, `llama3`
- `mistral`, `mixtral`
- `codellama`
- `phi`
- And many more from [Ollama Library](https://ollama.ai/library)

#### OpenAI (ChatGPT)

**Best for**: Production use with GPT-4, GPT-3.5

1. **Get API Key**: Visit [OpenAI Platform](https://platform.openai.com/api-keys)
2. **Enable OpenAI** in plugin settings
3. **Enter API Key**: Paste your key (starts with `sk-`)
4. **API URL**: Default is `https://api.openai.com/v1/chat/completions`

**Available Models**:
- `gpt-4o`
- `gpt-4-turbo`
- `gpt-4`
- `gpt-3.5-turbo`

#### Anthropic (Claude)

**Best for**: Advanced reasoning and long context

1. **Get API Key**: Visit [Anthropic Console](https://console.anthropic.com/settings/keys)
2. **Enable Anthropic** in plugin settings
3. **Enter API Key**: Paste your key (starts with `sk-ant-`)
4. **API URL**: Default is `https://api.anthropic.com/v1/messages`

**Available Models**:
- `claude-3-opus-20240229`
- `claude-3-sonnet-20240229`
- `claude-3-haiku-20240307`
- `claude-3-5-sonnet-20241022`

## 📖 Usage Guide

### Using the Chat Interface

1. Navigate to **Fluent MCP Agent → Chat** in WordPress admin
2. Select your preferred AI provider and model
3. Start chatting! The AI can:
   - Answer questions about your WordPress site
   - Execute WordPress abilities (if available)
   - Help with content creation
   - Provide technical assistance

### WordPress Abilities Integration

Fluent MCP Agent automatically discovers and exposes WordPress abilities registered via the Abilities API. These abilities become available as tools that the AI can call during conversations.

**Example Abilities** (from Fluent Abilities Hub):
- `fluentcart/get-orders` - Retrieve order information
- `fluentcart/create-coupon` - Create discount coupons
- `fluentcart/update-order-status` - Update order statuses
- And many more...

### Frontend Integration

The plugin includes a global JavaScript file (`assets/global.js`) that adds an "Ask Fluent" button when text is selected on the frontend. This allows users to quickly ask questions about selected content.

**Usage**:
1. Select any text on your WordPress site
2. Click the "Ask Fluent" popup button
3. A custom event `fluent:ask` is dispatched with the selected text

## 🏗️ Architecture

### Plugin Structure

```
fluent-mcp-agent/
├── fluent-mcp-agent.php    # Main plugin file
├── includes/
│   ├── admin-menu.php      # Admin menu and page rendering
│   ├── settings.php        # Settings page and fields
│   ├── mcp-servers.php     # MCP server management
│   ├── ajax.php            # AJAX handlers
│   └── helpers.php         # Helper functions
├── assets/
│   ├── global.js           # Frontend integration script
│   ├── index.css           # Styles
│   └── index.js            # Additional scripts
├── my-assistant-app/       # React chat application
│   ├── src/
│   │   ├── ChatShell.jsx   # Main chat component
│   │   ├── AssistantModal.jsx
│   │   └── components/
│   └── dist/               # Built assets
└── vendor/                 # Composer dependencies
    └── wordpress/
        └── mcp-adapter/    # MCP protocol adapter
```

### How It Works

```
User Input (Chat)
    ↓
React Chat Interface
    ↓
REST API: /fluent-mcp-agent/v1/api/chat
    ↓
Provider Proxy (Ollama/OpenAI/Claude)
    ↓
AI Model (with WordPress abilities as tools)
    ↓
Tool Call Detection
    ↓
WordPress Abilities API
    ↓
Execute Ability
    ↓
Return Result to AI
    ↓
Stream Response to User
```

### Key Components

1. **Provider Proxies**: Handle communication with different AI providers
2. **Streaming Support**: Real-time response streaming for better UX
3. **Tool/Function Calling**: Converts WordPress abilities to AI tool format
4. **MCP Integration**: Uses `wordpress/mcp-adapter` to bridge Abilities API to MCP protocol

## 🔧 Development

### Setting Up Development Environment

1. **Clone/Download** the plugin
2. **Install Dependencies**:
   ```bash
   composer install
   cd my-assistant-app && npm install
   ```

3. **Start Development Server** (for frontend):
   ```bash
   cd my-assistant-app
   npm run dev
   ```
   The plugin will automatically detect the Vite dev server at `http://localhost:5174/`

4. **Build for Production**:
   ```bash
   cd my-assistant-app
   npm run build
   ```

### Code Structure

#### Adding a New Provider

1. Add provider option in `includes/settings.php`
2. Create proxy function in `fluent-mcp-agent.php` (similar to `ollama_proxy_chat`, `openai_proxy_chat`)
3. Add provider case in `fluent_mcp_agent_determine_provider_proxy_chat()`
4. Update admin menu to show provider in model selector

#### Customizing the Chat Interface

The React app is in `my-assistant-app/src/`. Key files:
- `ChatShell.jsx`: Main chat component
- `utils/ChatTransportWithProvider.js`: Handles API communication
- `components/`: UI components

### REST API Endpoints

#### Chat Endpoint

**POST** `/wp-json/fluent-mcp-agent/v1/api/chat`

**Request Body**:
```json
{
  "provider": "ollama",
  "model": "llama2",
  "messages": [
    {
      "role": "user",
      "content": "Hello!"
    }
  ]
}
```

**Response**: Streaming text/plain format (assistant-ui protocol)

## 🐛 Troubleshooting

### Chat Not Loading

- **Check**: At least one provider is enabled in Settings
- **Check**: Default provider is selected
- **Check**: Browser console for JavaScript errors
- **Check**: REST API is accessible (`/wp-json/`)

### Ollama Connection Issues

- **Verify**: Ollama is running (`ollama serve`)
- **Test**: `curl http://localhost:11434/api/tags` should return models
- **Check**: URL in settings matches your Ollama instance
- **Firewall**: Ensure port 11434 is accessible

### OpenAI/Claude API Errors

- **Verify**: API key is correct and active
- **Check**: API key has sufficient credits/quota
- **Verify**: API URL is correct (defaults should work)
- **Check**: Network/firewall allows outbound HTTPS

### Abilities Not Available

- **Requires**: WordPress 6.9+ for Abilities API
- **Requires**: Abilities must be registered via `wp_register_ability()`
- **Check**: MCP Adapter is properly initialized
- **Verify**: Abilities are registered on `wp_abilities_api_init` hook

### Frontend Build Issues

- **Clear**: Delete `my-assistant-app/dist/` and rebuild
- **Check**: Node.js version (18+ recommended)
- **Reinstall**: `rm -rf node_modules && npm install`
- **Check**: Vite dev server is running if using dev mode

## 🔐 Security

- **API Keys**: Stored securely in WordPress options (encrypted in database)
- **Permissions**: Chat interface requires `manage_options` capability
- **REST API**: Uses WordPress REST API authentication
- **Ability Execution**: Respects WordPress ability permission callbacks
- **User Context**: Tool calls execute with administrator privileges (for ability execution)

## 📝 Changelog

### Version 0.1.0
- Initial release
- Support for Ollama, OpenAI, and Anthropic
- React-based chat interface
- WordPress Abilities API integration
- Streaming response support
- Tool/function calling support

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

GPL v2 or later

## 🔗 Related Projects

- **WordPress Abilities API**: Built-in WordPress 6.9+ feature
- **MCP Adapter**: `wordpress/mcp-adapter` package
- **Fluent Abilities Hub**: WordPress plugin that registers abilities for Fluent products
- **Ollama**: Local AI model runner

## 💡 Tips & Best Practices

1. **Start with Ollama**: Great for testing without API costs
2. **Use GPT-4 for Production**: Best tool calling support
3. **Monitor API Usage**: Keep track of API costs for OpenAI/Claude
4. **Test Abilities**: Ensure abilities are properly registered before use
5. **Development Mode**: Use Vite dev server for faster frontend iteration

## 🆘 Support

For issues, questions, or feature requests:
- Check the troubleshooting section above
- Review WordPress and plugin error logs
- Ensure all requirements are met
- Verify provider configurations

---

**Made with ❤️ for the WordPress community**

