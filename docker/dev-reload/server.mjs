import { createServer } from 'node:http';
import { readdir, stat } from 'node:fs/promises';
import { join, relative } from 'node:path';

const root = '/workspace';
const watchedRoots = ['api', 'plans', 'web'];
const ignoredDirectories = new Set(['.git', 'node_modules', 'vendor', 'storage', 'uploads']);
const watchedExtensions = new Set(['.css', '.js', '.mjs', '.php']);
const clients = new Set();

function shouldIgnore(path) {
  return path.startsWith('web/public/build/') || path.split('/').some((segment) => ignoredDirectories.has(segment));
}

function extension(path) {
  const dot = path.lastIndexOf('.');
  return dot === -1 ? '' : path.slice(dot);
}

async function scan(directory, files = new Map()) {
  const entries = await readdir(directory, { withFileTypes: true });

  for (const entry of entries) {
    const fullPath = join(directory, entry.name);
    const path = relative(root, fullPath);
    if (shouldIgnore(path)) continue;

    if (entry.isDirectory()) {
      await scan(fullPath, files);
      continue;
    }

    if (entry.isFile() && watchedExtensions.has(extension(path))) {
      const info = await stat(fullPath);
      files.set(path, `${info.mtimeMs}:${info.size}`);
    }
  }

  return files;
}

async function snapshot() {
  const files = new Map();
  await Promise.all(watchedRoots.map((directory) => scan(join(root, directory), files)));
  return files;
}

function changed(previous, next) {
  if (previous.size !== next.size) return true;
  for (const [path, signature] of next) if (previous.get(path) !== signature) return true;
  return false;
}

function broadcast() {
  for (const client of clients) client.write('event: reload\ndata: now\n\n');
}

let known = await snapshot();
let scanning = false;
setInterval(async () => {
  if (scanning) return;
  scanning = true;
  try {
    const next = await snapshot();
    if (changed(known, next)) broadcast();
    known = next;
  } catch (error) {
    console.error('Development reload scan failed:', error);
  } finally {
    scanning = false;
  }
}, 500);

createServer((request, response) => {
  if (request.url !== '/events') {
    response.writeHead(404).end();
    return;
  }

  response.writeHead(200, {
    'Content-Type': 'text/event-stream',
    'Cache-Control': 'no-cache, no-transform',
    Connection: 'keep-alive',
  });
  response.write(': connected\n\n');
  clients.add(response);
  request.on('close', () => clients.delete(response));
}).listen(35729, '0.0.0.0', () => console.log('Development reload server listening on :35729'));
