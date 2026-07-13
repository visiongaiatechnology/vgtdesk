/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra DOM-safe renderers.
 */

window.VGTAstraRenderers = (() => {
    'use strict';

    function renderStructuralMap(mapData, nodes, createTextElement) {
        nodes.mapTree.replaceChildren();
        const entries = Object.entries(mapData || {});
        if (entries.length === 0) {
            nodes.mapTree.appendChild(createTextElement('div', 'vgta-placeholder-text', 'No indexed files found.'));
            return;
        }

        entries.forEach(([filepath, details]) => {
            const item = document.createElement('div');
            item.className = 'vgta-tree-item';
            if (details.is_primary) {
                item.classList.add('primary-file');
            }

            const summaryElement = document.createElement('div');
            summaryElement.className = 'vgta-tree-item-summary';
            summaryElement.appendChild(createTextElement('span', 'path', filepath));

            const badges = document.createElement('div');
            badges.className = 'meta-badges';
            if (details.too_large) {
                badges.appendChild(createTextElement('span', 'vgta-file-badge danger', 'SKIPPED'));
            } else {
                badges.appendChild(createTextElement('span', 'vgta-file-badge', `${(Number(details.size || 0) / 1024).toFixed(1)} KB`));
                const hookCount = Array.isArray(details.registered_hooks) ? details.registered_hooks.length : 0;
                const classCount = Array.isArray(details.classes) ? details.classes.length : 0;
                if (hookCount > 0) {
                    badges.appendChild(createTextElement('span', 'vgta-file-badge hook', `HOOKS ${hookCount}`));
                }
                if (classCount > 0) {
                    badges.appendChild(createTextElement('span', 'vgta-file-badge class', `CLASSES ${classCount}`));
                }
                if (details.security_notes && details.security_notes !== 'Secure' && details.security_notes !== 'Not audited.') {
                    badges.appendChild(createTextElement('span', 'vgta-file-badge warning', 'AUDIT'));
                }
            }

            summaryElement.appendChild(badges);
            item.appendChild(summaryElement);
            const detailsBox = document.createElement('div');
            detailsBox.className = 'vgta-tree-item-body';
            detailsBox.appendChild(createTextElement('div', 'details-label', 'PURPOSE:'));
            detailsBox.appendChild(createTextElement('div', 'details-val', details.purpose || 'No description.'));
            if (details.security_notes) {
                detailsBox.appendChild(createTextElement('div', 'details-label sec-label', 'SECURITY AUDIT:'));
                const secText = createTextElement('div', 'details-val sec-val', details.security_notes);
                if (details.security_notes !== 'Secure' && details.security_notes !== 'Not audited.') {
                    secText.classList.add('security-alert');
                }
                detailsBox.appendChild(secText);
            }
            if (Array.isArray(details.classes) && details.classes.length > 0) {
                detailsBox.appendChild(createTextElement('div', 'details-label', 'CLASSES:'));
                detailsBox.appendChild(createTextElement('div', 'details-val list', details.classes.join(', ')));
            }
            if (Array.isArray(details.hooks) && details.hooks.length > 0) {
                detailsBox.appendChild(createTextElement('div', 'details-label', 'HOOKS:'));
                detailsBox.appendChild(createTextElement('div', 'details-val list', details.hooks.join(', ')));
            }
            item.appendChild(detailsBox);
            nodes.mapTree.appendChild(item);
        });
    }

    function appendPlainMessage(kind, meta, content, nodes, createTextElement) {
        const box = document.createElement('div');
        box.className = `vgta-message ${kind}`;
        box.appendChild(createTextElement('div', 'vgta-message-meta', meta));
        const body = document.createElement('div');
        body.className = 'vgta-message-body';
        const contentDiv = document.createElement('div');
        contentDiv.className = 'vgta-response-content';
        renderMarkdownSafely(String(content), contentDiv, createTextElement);
        body.appendChild(contentDiv);
        box.appendChild(body);
        nodes.chatLog.appendChild(box);
        nodes.chatLog.scrollTop = nodes.chatLog.scrollHeight;
    }

    function appendRichAssistantMessage(role, model, content, reasoning, nodes, createTextElement) {
        const box = document.createElement('div');
        box.className = 'vgta-message assistant';
        box.appendChild(createTextElement('div', 'vgta-message-meta', `${role} - ${model}`));
        const body = document.createElement('div');
        body.className = 'vgta-message-body';
        if (reasoning) {
            const details = document.createElement('details');
            details.className = 'vgta-thinking-collapse';
            const summary = document.createElement('summary');
            summary.textContent = 'SYSTEM BRAIN ENGINE / REASONING';
            details.appendChild(summary);
            const thinkingDiv = document.createElement('div');
            thinkingDiv.className = 'vgta-thinking-content';
            thinkingDiv.textContent = reasoning;
            details.appendChild(thinkingDiv);
            body.appendChild(details);
        }
        const responseDiv = document.createElement('div');
        responseDiv.className = 'vgta-response-content';
        body.appendChild(responseDiv);
        box.appendChild(body);
        nodes.chatLog.appendChild(box);
        nodes.chatLog.scrollTop = nodes.chatLog.scrollHeight;

        const text = String(content);
        let currentPos = 0;
        const chunkSize = 16;
        const intervalMs = 15;

        function tick() {
            currentPos += chunkSize;
            if (currentPos >= text.length) {
                currentPos = text.length;
                responseDiv.replaceChildren();
                renderMarkdownSafely(text, responseDiv, createTextElement);
                nodes.chatLog.scrollTop = nodes.chatLog.scrollHeight;
                return;
            }
            responseDiv.replaceChildren();
            renderMarkdownSafely(text.slice(0, currentPos), responseDiv, createTextElement);
            
            const cursor = document.createElement('span');
            cursor.className = 'vgta-typing-cursor';
            cursor.textContent = '█';
            responseDiv.appendChild(cursor);
            
            nodes.chatLog.scrollTop = nodes.chatLog.scrollHeight;
            window.setTimeout(tick, intervalMs);
        }
        tick();
    }

    function renderMarkdownSafely(text, container, createTextElement) {
        const lines = text.split('\n');
        let inCodeBlock = false;
        let codeLines = [];
        for (let index = 0; index < lines.length; index += 1) {
            const line = lines[index];
            if (line.trim().startsWith('```')) {
                if (inCodeBlock) {
                    appendSafeCodeBlock(container, codeLines.join('\n'));
                    codeLines = [];
                    inCodeBlock = false;
                } else {
                    inCodeBlock = true;
                }
                continue;
            }
            if (inCodeBlock) {
                codeLines.push(line);
                continue;
            }

            if (line.trim() === '') {
                continue;
            }

            if (isTableStart(lines, index)) {
                index = appendSafeMarkdownTable(container, lines, index);
                continue;
            }

            if (isListLine(line)) {
                index = appendSafeMarkdownList(container, lines, index);
                continue;
            }

            const headingMatch = line.match(/^(#{1,3})\s+(.+)$/);
            if (headingMatch) {
                const heading = document.createElement(`h${headingMatch[1].length}`);
                heading.className = `vgta-md-h${headingMatch[1].length}`;
                appendInlineMarkdown(heading, headingMatch[2]);
                container.appendChild(heading);
                continue;
            }

            const paragraph = document.createElement('p');
            paragraph.className = 'vgta-chat-text';
            appendInlineMarkdown(paragraph, line);
            container.appendChild(paragraph);
        }
        if (inCodeBlock && codeLines.length > 0) {
            appendSafeCodeBlock(container, codeLines.join('\n'));
        }
    }

    function appendInlineMarkdown(container, text) {
        const source = String(text);
        let cursor = 0;

        while (cursor < source.length) {
            const nextBold = source.indexOf('**', cursor);
            const nextCode = source.indexOf('`', cursor);
            const tokenIndex = minPositive(nextBold, nextCode);

            if (tokenIndex === -1) {
                container.appendChild(document.createTextNode(source.slice(cursor)));
                break;
            }

            if (tokenIndex > cursor) {
                container.appendChild(document.createTextNode(source.slice(cursor, tokenIndex)));
            }

            if (tokenIndex === nextBold) {
                const end = source.indexOf('**', tokenIndex + 2);
                if (end === -1) {
                    container.appendChild(document.createTextNode(source.slice(tokenIndex)));
                    break;
                }

                const strong = document.createElement('strong');
                strong.textContent = source.slice(tokenIndex + 2, end);
                container.appendChild(strong);
                cursor = end + 2;
                continue;
            }

            const end = source.indexOf('`', tokenIndex + 1);
            if (end === -1) {
                container.appendChild(document.createTextNode(source.slice(tokenIndex)));
                break;
            }

            const code = document.createElement('code');
            code.className = 'vgta-inline-code';
            code.textContent = source.slice(tokenIndex + 1, end);
            container.appendChild(code);
            cursor = end + 1;
        }
    }

    function minPositive(first, second) {
        if (first === -1) {
            return second;
        }
        if (second === -1) {
            return first;
        }
        return Math.min(first, second);
    }

    function isTableStart(lines, index) {
        if (!isTableRow(lines[index])) {
            return false;
        }

        const separatorIndex = nextNonEmptyIndex(lines, index + 1);
        return separatorIndex !== -1 && isTableSeparator(lines[separatorIndex]);
    }

    function appendSafeMarkdownTable(container, lines, startIndex) {
        const tableLines = [];
        let index = startIndex;

        while (index < lines.length) {
            if (lines[index].trim() === '') {
                const nextIndex = nextNonEmptyIndex(lines, index + 1);
                if (nextIndex !== -1 && isTableRow(lines[nextIndex])) {
                    index = nextIndex;
                    continue;
                }
                break;
            }

            if (!isTableRow(lines[index])) {
                break;
            }

            tableLines.push(lines[index]);
            index += 1;
        }

        const headerCells = splitTableRow(tableLines[0]);
        const bodyLines = tableLines.filter((row, rowIndex) => rowIndex > 1 && !isTableSeparator(row));
        const wrapper = document.createElement('div');
        wrapper.className = 'vgta-table-container';
        const table = document.createElement('table');
        table.className = 'vgta-command-table';
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        headerCells.forEach((cell) => {
            const th = document.createElement('th');
            appendInlineMarkdown(th, cell);
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);

        const tbody = document.createElement('tbody');
        bodyLines.forEach((row) => {
            const tr = document.createElement('tr');
            splitTableRow(row).forEach((cell) => {
                const td = document.createElement('td');
                appendInlineMarkdown(td, cell);
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        wrapper.appendChild(table);
        container.appendChild(wrapper);

        return index - 1;
    }

    function isTableRow(line) {
        const trimmed = String(line || '').trim();
        return trimmed.includes('|') && trimmed.replace(/\|/g, '').trim() !== '';
    }

    function isTableSeparator(line) {
        return /^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?\s*$/.test(String(line || ''));
    }

    function splitTableRow(line) {
        const trimmed = String(line || '').trim().replace(/^\|/, '').replace(/\|$/, '');
        return trimmed.split('|').map((cell) => cell.trim());
    }

    function appendSafeMarkdownList(container, lines, startIndex) {
        const firstMatch = parseListLine(lines[startIndex]);
        const ordered = Boolean(firstMatch && firstMatch.ordered);
        const list = document.createElement(ordered ? 'ol' : 'ul');
        list.className = ordered ? 'vgta-md-ol' : 'vgta-md-ul';
        let index = startIndex;

        while (index < lines.length) {
            if (lines[index].trim() === '') {
                const nextIndex = nextNonEmptyIndex(lines, index + 1);
                const nextMatch = nextIndex === -1 ? null : parseListLine(lines[nextIndex]);
                if (nextMatch && nextMatch.ordered === ordered) {
                    index = nextIndex;
                    continue;
                }
                break;
            }

            const match = parseListLine(lines[index]);
            if (!match || match.ordered !== ordered) {
                break;
            }

            const item = document.createElement('li');
            appendInlineMarkdown(item, match.content);
            list.appendChild(item);
            index += 1;
        }

        container.appendChild(list);
        return index - 1;
    }

    function isListLine(line) {
        return parseListLine(line) !== null;
    }

    function parseListLine(line) {
        const value = String(line || '');
        const ordered = value.match(/^\s*\d+\.\s+(.+)$/);
        if (ordered) {
            return { ordered: true, content: ordered[1] };
        }

        const unordered = value.match(/^\s*[-*]\s+(.+)$/);
        if (unordered) {
            return { ordered: false, content: unordered[1] };
        }

        return null;
    }

    function nextNonEmptyIndex(lines, index) {
        for (let cursor = index; cursor < lines.length; cursor += 1) {
            if (String(lines[cursor] || '').trim() !== '') {
                return cursor;
            }
        }
        return -1;
    }

    function appendSafeCodeBlock(container, codeText) {
        const pre = document.createElement('pre');
        pre.className = 'vgta-code-container';
        const code = document.createElement('code');
        code.textContent = codeText;
        pre.appendChild(code);
        container.appendChild(pre);
    }

    return { renderStructuralMap, appendPlainMessage, appendRichAssistantMessage };
})();
