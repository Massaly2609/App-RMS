// import { apiClient } from './client';

// export type TimelinePost = {
//   id: number;
//   user_id: number;
//   type: string;
//   content: string | null;
//   media_url: string | null;
//   visibility: string;
//   country: string | null;
//   city: string | null;
//   status: string;
//   created_at: string;
//   updated_at: string;
//   likes_count?: number;
//   comments_count?: number;
// };

// export type TimelineResponse = {
//   data: TimelinePost[];
//   current_page: number;
//   last_page: number;
//   per_page: number;
//   total: number;
// };

// export type TimelineComment = {
//   id: number;
//   timeline_post_id: number;
//   user_id: number;
//   content: string;
//   created_at: string;
//   updated_at: string;
//   user?: {
//     id: number;
//     name: string;
//   };
// };

// export async function fetchTimeline(page = 1, type?: string) {
//   const params: any = { page };
//   if (type) params.type = type;

//   const response = await apiClient.get('/timeline/posts', { params });
//   return response.data.data as TimelineResponse;
// }


// export async function createPost(content: string) {
//   const response = await apiClient.post('/timeline/posts', {
//     content,
//     type: 'text',
//   });
//   return response.data.data.post as TimelinePost;
// }

// export async function toggleLike(postId: number) {
//   const response = await apiClient.post(`/timeline/posts/${postId}/like`);
//   return response.data.data as { liked: boolean };
// }


// export async function addComment(postId: number, comment: string) {
//   const response = await apiClient.post(`/timeline/posts/${postId}/comment`, {
//     comment,
//   });
//   return response.data.data.comment;
// }

// export async function fetchComments(postId: number) {
//   const res = await apiClient.get(`/timeline/posts/${postId}/comments`);
//   return res.data.data as TimelineComment[];
// }

// export async function createComment(postId: number, content: string) {
//   const res = await apiClient.post(`/timeline/posts/${postId}/comments`, {
//     content,
//   });
//   return res.data.data as TimelineComment;
// }

// export async function updateComment(postId: number, commentId: number, content: string) {
//   const res = await apiClient.patch(
//     `/timeline/posts/${postId}/comments/${commentId}`,
//     { content },
//   );
//   return res.data.data as TimelineComment;
// }


import { apiClient } from './client';

// --- Types posts ---

export type TimelinePost = {
  id: number;
  user_id: number;
  type: string;
  content: string | null;
  media_url: string | null;
  visibility: string;
  country: string | null;
  city: string | null;
  status: string;
  created_at: string;
  updated_at: string;
  likes_count?: number;
  comments_count?: number;
};

export type TimelineResponse = {
  data: TimelinePost[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

// --- Types commentaires ---

export type TimelineComment = {
  id: number;
  timeline_post_id: number;
  user_id: number;
  content: string;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    name: string;
  };
};

// --- API posts ---

export async function fetchTimeline(page = 1, type?: string) {
  const params: any = { page };
  if (type) params.type = type;

  const response = await apiClient.get('/timeline/posts', { params });

  // Laravel renvoie: { status, data: { ...pagination... } }
  return response.data.data as TimelineResponse;
}

export async function createPost(content: string) {
  const response = await apiClient.post('/timeline/posts', {
    content,
    type: 'text',
  });

  // Laravel renvoie: { status, data: { post: ... } }
  return response.data.data.post as TimelinePost;
}

export async function toggleLike(postId: number) {
  const response = await apiClient.post(`/timeline/posts/${postId}/like`);
  // { status, data: { liked: boolean } }
  return response.data.data as { liked: boolean };
}

// --- API commentaires (nouveau système) ---

export async function fetchComments(postId: number) {
  const res = await apiClient.get(`/timeline/posts/${postId}/comments`);
  // { data: [ ...comments ] }
  return res.data.data as TimelineComment[];
}

export async function createComment(postId: number, content: string) {
  const res = await apiClient.post(`/timeline/posts/${postId}/comments`, {
    content,
  });
  // { data: { ...comment } }
  return res.data.data as TimelineComment;
}

export async function updateComment(postId: number, commentId: number, content: string) {
  const res = await apiClient.patch(
    `/timeline/posts/${postId}/comments/${commentId}`,
    { content },
  );
  // { data: { ...comment } }
  return res.data.data as TimelineComment;
}
