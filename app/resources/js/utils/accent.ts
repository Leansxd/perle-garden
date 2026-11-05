/**
 * Ayarlardaki metinde *yıldız arası* kelimeleri altın italik vurguya çevirir.
 * Örn: "Denize yakın *bahçe içinde* yaşam". Metin önce HTML-escape edilir.
 */
export function accent(text: string | null | undefined): string {
    const safe = (text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]!);
    // Vurgudan hemen sonra gelen noktalama (", . ; : ! ?") vurguyla aynı satırda kalsın
    return safe
        .replace(/\*(.+?)\*([.,;:!?…]*)/g, (_, word: string, punct: string) =>
            punct ? `<span class="whitespace-nowrap"><em class="accent">${word}</em>${punct}</span>` : `<em class="accent">${word}</em>`,
        )
        .replace(/\n/g, '<br>');
}

export const plain = (text: string | null | undefined) => (text ?? '').replace(/\*/g, '');
