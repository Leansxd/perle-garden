/**
 * Ayarlardaki metinde *yıldız arası* kelimeleri altın italik vurguya çevirir.
 * Örn: "Denize yakın *bahçe içinde* yaşam". Metin önce HTML-escape edilir.
 */
export function accent(text: string | null | undefined): string {
    const safe = (text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]!);
    return safe.replace(/\*(.+?)\*/g, '<em class="accent">$1</em>').replace(/\n/g, '<br>');
}

export const plain = (text: string | null | undefined) => (text ?? '').replace(/\*/g, '');
