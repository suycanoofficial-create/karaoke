export function sanitize(input: string): string {
  return input
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

export function parseDuration(duration: string): string {
  const match = duration.match(/(\d+)\s*(second|minute|hour|day|week|month|year)s?/);
  if (!match) return duration;
  const num = parseInt(match[1]);
  const unit = match[2];
  const unitMap: Record<string, string> = {
    second: 'seconds', minute: 'minutes', hour: 'hours',
    day: 'days', week: 'weeks', month: 'months', year: 'years',
  };
  return `-${num} ${unitMap[unit] || unit + 's'}`;
}

export function isValidRoomCode(code: string): boolean {
  return /^[A-Z0-9]{4,8}$/.test(code);
}
