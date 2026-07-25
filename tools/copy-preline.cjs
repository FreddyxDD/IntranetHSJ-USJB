const { copyFileSync, mkdirSync } = require('node:fs');
const { dirname, resolve } = require('node:path');

const source = resolve(__dirname, '../node_modules/preline/dist/preline.js');
const destination = resolve(__dirname, '../public/assets/vendor/preline/preline.js');

mkdirSync(dirname(destination), { recursive: true });
copyFileSync(source, destination);

console.log('Preline publicado en public/assets/vendor/preline/preline.js');
