import { AttachmentBuilder } from 'discord.js';
import { createCanvas, loadImage } from '@napi-rs/canvas';

export async function buildRulesAcknowledgementAttachment(client, acknowledgement) {
  if (!acknowledgement?.total || !Array.isArray(acknowledgement.users) || acknowledgement.users.length === 0) {
    return null;
  }

  const users = acknowledgement.users.slice(0, 3);
  const size = 56;
  const overlap = 16;
  const startX = 18;
  const startY = 14;
  const avatarCount = users.length;
  const pillWidth = acknowledgement.total > 3 ? 94 : 0;
  const width = Math.max(220, startX + (avatarCount * (size - overlap)) + overlap + pillWidth + 24);
  const height = 84;
  const canvas = createCanvas(width, height);
  const context = canvas.getContext('2d');

  context.fillStyle = '#0b1020';
  roundRect(context, 0, 0, width, height, 20);
  context.fill();

  context.fillStyle = '#8b5cf6';
  roundRect(context, 8, 8, width - 16, height - 16, 16);
  context.globalAlpha = 0.15;
  context.fill();
  context.globalAlpha = 1;

  let x = startX;

  for (const item of users) {
    const user = await client.users.fetch(item.discord_user_id).catch(() => null);
    if (!user) {
      continue;
    }

    const image = await loadImage(user.displayAvatarURL({ extension: 'png', size: 128 }));

    context.save();
    context.beginPath();
    context.arc(x + (size / 2), startY + (size / 2), size / 2, 0, Math.PI * 2);
    context.closePath();
    context.clip();
    context.drawImage(image, x, startY, size, size);
    context.restore();

    context.strokeStyle = '#f5f3ff';
    context.lineWidth = 3;
    context.beginPath();
    context.arc(x + (size / 2), startY + (size / 2), (size / 2) - 1.5, 0, Math.PI * 2);
    context.stroke();

    x += size - overlap;
  }

  if (acknowledgement.total > 3) {
    const remaining = acknowledgement.total - 3;
    const pillX = x + 6;
    const pillY = 24;
    const pillHeight = 36;
    const pillText = `+${remaining} lainnya`;

    context.fillStyle = '#1f1638';
    roundRect(context, pillX, pillY, 92, pillHeight, 18);
    context.fill();

    context.fillStyle = '#f5f3ff';
    context.font = 'bold 15px Arial';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.fillText(pillText, pillX + 46, pillY + 18);
  }

  return new AttachmentBuilder(await canvas.encode('png'), {
    name: 'rules-ack-strip.png',
  });
}

function roundRect(context, x, y, width, height, radius) {
  context.beginPath();
  context.moveTo(x + radius, y);
  context.lineTo(x + width - radius, y);
  context.quadraticCurveTo(x + width, y, x + width, y + radius);
  context.lineTo(x + width, y + height - radius);
  context.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
  context.lineTo(x + radius, y + height);
  context.quadraticCurveTo(x, y + height, x, y + height - radius);
  context.lineTo(x, y + radius);
  context.quadraticCurveTo(x, y, x + radius, y);
  context.closePath();
}
