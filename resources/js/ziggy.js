const Ziggy = {"url":"http:\/\/localhost:8000","port":8000,"defaults":{},"routes":{"dashboard":{"uri":"\/","methods":["GET","HEAD"]},"messages.store":{"uri":"messages","methods":["POST"]},"webhook.verify":{"uri":"webhook","methods":["GET","HEAD"]},"webhook.handle":{"uri":"webhook","methods":["POST"]},"storage.local":{"uri":"storage\/{path}","methods":["GET","HEAD"],"wheres":{"path":".*"},"parameters":["path"]},"storage.local.upload":{"uri":"storage\/{path}","methods":["PUT"],"wheres":{"path":".*"},"parameters":["path"]}}};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
  Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
