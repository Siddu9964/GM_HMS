const fs = require('fs');
const js = fs.readFileSync('test.js', 'utf8');
const lines = js.split('\n');
let braces = 0;
let inString = false, strChar = '';
let inComment = false, inMultilineComment = false;
let trace = [];
for (let l = 0; l < lines.length; l++) {
    const jsLine = lines[l];
    let lineBraces = 0;
    for (let i = 0; i < jsLine.length; i++) {
        const c = jsLine[i];
        const next = jsLine[i+1];
        if (inMultilineComment) { if (c === '*' && next === '/') { inMultilineComment = false; i++; } continue; }
        if (inComment) { break; } // line comment ends at \n
        if (inString) {
            if (c === '\\') { i++; continue; }
            if (c === strChar) { inString = false; }
            continue;
        }
        if (c === '/' && next === '/') { inComment = true; break; }
        if (c === '/' && next === '*') { inMultilineComment = true; i++; continue; }
        if (c === '"' || c === "'" || c === "`") { inString = true; strChar = c; continue; }
        if (c === '{') { braces++; lineBraces++; }
        if (c === '}') { braces--; lineBraces--; }
    }
    inComment = false;
    
    // if line finishes with braces == 0, it means we are at top level
    if (lineBraces !== 0 && (braces === 1 || braces === 0)) {
        trace.push({ line: l + 1, braces, jsLine: jsLine.substring(0, 80).trim() });
    }
}
console.log("Functions trace:");
for (let i = 0; i < trace.length; i++) {
    if (trace[i].braces === 1 && trace[i].jsLine.includes('function ')) {
        console.log(trace[i].line, '=>', trace[i].jsLine);
    } else if (trace[i].braces === 0 && trace[i].jsLine === '}') {
        console.log('  ends at', trace[i].line);
    }
}
