import * as pdfjsLib from 'pdfjs-dist/legacy/build/pdf.mjs';
import pdfjsWorker from 'pdfjs-dist/legacy/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

export async function loadLegacyPdfDocument(pdfUrl) {
    const options = {
        disableFontFace: true,
        useSystemFonts: true,
        isEvalSupported: false,
    };

    try {
        const response = await fetch(pdfUrl, {
            credentials: 'same-origin',
            headers: { Accept: 'application/pdf' },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.arrayBuffer();

        return await pdfjsLib.getDocument({ data, ...options }).promise;
    } catch {
        return pdfjsLib.getDocument({
            url: pdfUrl,
            withCredentials: true,
            rangeChunkSize: 65536,
            ...options,
        }).promise;
    }
}

export { pdfjsLib };
