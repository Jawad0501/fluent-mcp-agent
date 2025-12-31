import { AssistantChatTransport as BaseTransport } from "@assistant-ui/react-ai-sdk";

export class ChatTransportWithProvider extends BaseTransport {
  constructor({ provider, model, ...rest }) {
    super(rest);
    this.provider = provider;
    this.model = model;
  }

  // override prepareSendMessagesRequest by wrapping super
  async prepareSendMessagesRequest(options) {
    const original = await super.prepareSendMessagesRequest(options);
    console.log(original, 'original')
    // ensure body exists
    const body = original.body ?? {};

    // inject provider + model
    body.provider = this.provider;
    body.model = this.model;

    return {
      ...original,
      body,
    };
  }
}
