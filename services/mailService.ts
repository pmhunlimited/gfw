
import { Post, Subscriber } from '../types';

export async function sendSubscriberNotification(post: Post, subscribers: Subscriber[]) {
  console.log(`[SMTP SIMULATOR] Sending new post notification to ${subscribers.length} subscribers...`);
  subscribers.forEach(sub => {
    console.log(`[SMTP] To: ${sub.email} | Subject: New Post: ${post.title}`);
  });
  return true;
}

export async function sendPasswordResetToken(email: string) {
  const token = Math.floor(100000 + Math.random() * 900000);
  console.log(`[SMTP SIMULATOR] Sending reset token ${token} to ${email}`);
  localStorage.setItem('admin_reset_token', token.toString());
  return true;
}
