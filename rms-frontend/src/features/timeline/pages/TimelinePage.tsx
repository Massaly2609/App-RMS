import { useEffect, useState, type FormEvent } from 'react';
import {
  createPost,
  fetchTimeline,
  type TimelinePost,
  toggleLike,
} from '../../../api/timeline';
import {
  fetchComments,
  createComment,
  updateComment,
  type TimelineComment,
} from '../../../api/timeline';

type TimelineState = {
  posts: TimelinePost[];
  loading: boolean;
  error: string | null;
  page: number;
  lastPage: number;
};

type CommentInputState = {
  [postId: number]: string;
};

type CommentsByPostState = {
  [postId: number]: {
    loading: boolean;
    loaded: boolean;
    items: TimelineComment[];
  };
};

type EditingCommentState = {
  [commentId: number]: string;
};

type EditingStatusState = {
  [commentId: number]: boolean;
};

export function TimelinePage() {
  const [state, setState] = useState<TimelineState>({
    posts: [],
    loading: true,
    error: null,
    page: 1,
    lastPage: 1,
  });

  const [newPost, setNewPost] = useState('');
  const [creating, setCreating] = useState(false);

  const [commentInputs, setCommentInputs] = useState<CommentInputState>({});
  const [commentsByPost, setCommentsByPost] = useState<CommentsByPostState>({});
  const [commentLoading, setCommentLoading] = useState<number | null>(null);
  const [likeLoading, setLikeLoading] = useState<number | null>(null);

  const [editingCommentValues, setEditingCommentValues] = useState<EditingCommentState>({});
  const [editingLoading, setEditingLoading] = useState<EditingStatusState>({});

  async function load(page = 1) {
    setState((s) => ({ ...s, loading: true, error: null }));
    try {
      const res = await fetchTimeline(page);
      setState({
        posts: res.data,
        loading: false,
        error: null,
        page: res.current_page,
        lastPage: res.last_page,
      });
    } catch (e: any) {
      setState((s) => ({
        ...s,
        loading: false,
        error: e.response?.data?.message ?? 'Erreur lors du chargement de la timeline.',
      }));
    }
  }

  useEffect(() => {
    load(1);
  }, []);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    if (!newPost.trim()) return;

    setCreating(true);
    try {
      const post = await createPost(newPost.trim());
      setNewPost('');
      setState((s) => ({ ...s, posts: [post, ...s.posts] }));
    } catch (e: any) {
      alert(e.response?.data?.message ?? 'Erreur lors de la création du post.');
    } finally {
      setCreating(false);
    }
  }

  function formatDate(dateStr: string) {
    return new Date(dateStr).toLocaleString();
  }

  async function handleToggleLike(post: TimelinePost) {
    setLikeLoading(post.id);
    try {
      const res = await toggleLike(post.id);
      const liked = res.liked;

      setState((s) => ({
        ...s,
        posts: s.posts.map((p) =>
          p.id === post.id
            ? {
                ...p,
                likes_count: (p.likes_count ?? 0) + (liked ? 1 : -1),
              }
            : p,
        ),
      }));
    } catch (e: any) {
      alert(e.response?.data?.message ?? 'Erreur lors du like.');
    } finally {
      setLikeLoading(null);
    }
  }

  async function handleLoadComments(postId: number) {
    const existing = commentsByPost[postId];
    if (existing?.loaded || existing?.loading) return;

    setCommentsByPost((prev) => ({
      ...prev,
      [postId]: {
        loading: true,
        loaded: false,
        items: prev[postId]?.items ?? [],
      },
    }));

    try {
      const items = await fetchComments(postId);
      setCommentsByPost((prev) => ({
        ...prev,
        [postId]: {
          loading: false,
          loaded: true,
          items,
        },
      }));
    } catch (e: any) {
      setCommentsByPost((prev) => ({
        ...prev,
        [postId]: {
          loading: false,
          loaded: false,
          items: prev[postId]?.items ?? [],
        },
      }));
      alert(e.response?.data?.message ?? 'Erreur lors du chargement des commentaires.');
    }
  }

  async function handleAddComment(e: FormEvent, post: TimelinePost) {
    e.preventDefault();
    const text = commentInputs[post.id]?.trim();
    if (!text) return;

    setCommentLoading(post.id);
    try {
      const newComment = await createComment(post.id, text);
      setCommentInputs((inputs) => ({ ...inputs, [post.id]: '' }));

      setCommentsByPost((prev) => {
        const current = prev[post.id] ?? { loading: false, loaded: true, items: [] };
        return {
          ...prev,
          [post.id]: {
            ...current,
            loaded: true,
            items: [newComment, ...current.items],
          },
        };
      });

      setState((s) => ({
        ...s,
        posts: s.posts.map((p) =>
          p.id === post.id
            ? {
                ...p,
                comments_count: (p.comments_count ?? 0) + 1,
              }
            : p,
        ),
      }));
    } catch (e: any) {
      alert(e.response?.data?.message ?? 'Erreur lors de l’ajout du commentaire.');
    } finally {
      setCommentLoading(null);
    }
  }

  function handleCommentInputChange(postId: number, value: string) {
    setCommentInputs((inputs) => ({ ...inputs, [postId]: value }));
  }

  function startEditingComment(comment: TimelineComment) {
    setEditingCommentValues((prev) => ({
      ...prev,
      [comment.id]: comment.content,
    }));
  }

  function cancelEditingComment(commentId: number) {
    setEditingCommentValues((prev) => {
      const copy = { ...prev };
      delete copy[commentId];
      return copy;
    });
  }

  async function saveEditingComment(postId: number, comment: TimelineComment) {
    const newContent = editingCommentValues[comment.id]?.trim();
    if (!newContent || newContent === comment.content) {
      cancelEditingComment(comment.id);
      return;
    }

    setEditingLoading((prev) => ({ ...prev, [comment.id]: true }));
    try {
      const updated = await updateComment(postId, comment.id, newContent);

      setCommentsByPost((prev) => {
        const current = prev[postId];
        if (!current) return prev;
        return {
          ...prev,
          [postId]: {
            ...current,
            items: current.items.map((c) =>
              c.id === comment.id ? updated : c,
            ),
          },
        };
      });

      cancelEditingComment(comment.id);
    } catch (e: any) {
      alert(e.response?.data?.message ?? 'Erreur lors de la modification du commentaire.');
    } finally {
      setEditingLoading((prev) => ({ ...prev, [comment.id]: false }));
    }
  }

  return (
    <div style={{ maxWidth: 800, margin: '24px auto', padding: 24 }}>
      <h1>Timeline RMS</h1>

      <section style={{ marginBottom: 24, padding: 16, border: '1px solid #ddd', borderRadius: 8 }}>
        <h2>Publier un message</h2>
        <form onSubmit={handleSubmit}>
          <textarea
            value={newPost}
            onChange={(e) => setNewPost(e.target.value)}
            rows={3}
            style={{ width: '100%', marginBottom: 8 }}
            placeholder="Partage ton expérience, un remerciement ou un témoignage..."
          />
          <button type="submit" disabled={creating}>
            {creating ? 'Publication...' : 'Publier'}
          </button>
        </form>
      </section>

      <section style={{ padding: 16, border: '1px solid #ddd', borderRadius: 8 }}>
        <h2>Flux communautaire</h2>

        {state.loading && <p>Chargement de la timeline...</p>}
        {state.error && <p style={{ color: 'red' }}>{state.error}</p>}

        {!state.loading && state.posts.length === 0 && (
          <p>Aucune publication pour le moment.</p>
        )}

        <ul style={{ listStyle: 'none', padding: 0 }}>
          {state.posts.map((post) => {
            const commentsState = commentsByPost[post.id];
            const comments = commentsState?.items ?? [];
            const commentsLoading = commentsState?.loading;

            return (
              <li
                key={post.id}
                style={{
                  borderBottom: '1px solid #eee',
                  padding: '12px 0',
                }}
              >
                <p style={{ margin: 0, fontSize: 12, color: '#666' }}>
                  {formatDate(post.created_at)} • type: {post.type}
                </p>
                <p style={{ marginTop: 4 }}>{post.content}</p>

                <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginTop: 4 }}>
                  <button
                    onClick={() => handleToggleLike(post)}
                    disabled={likeLoading === post.id}
                    style={{ fontSize: 12 }}
                  >
                    {likeLoading === post.id ? '...' : 'J’aime'}
                  </button>
                  <span style={{ fontSize: 12, color: '#999' }}>
                    {(post.likes_count ?? 0)} likes • {(post.comments_count ?? 0)} commentaires
                  </span>
                  <button
                    type="button"
                    onClick={() => handleLoadComments(post.id)}
                    disabled={commentsLoading}
                    style={{ fontSize: 12 }}
                  >
                    {commentsLoading ? 'Commentaires...' : 'Afficher les commentaires'}
                  </button>
                </div>

                {/* Formulaire commentaire */}
                <form
                  onSubmit={(e) => handleAddComment(e, post)}
                  style={{ marginTop: 8, display: 'flex', gap: 8 }}
                >
                  <input
                    type="text"
                    placeholder="Ajouter un commentaire..."
                    value={commentInputs[post.id] ?? ''}
                    onChange={(e) => handleCommentInputChange(post.id, e.target.value)}
                    style={{ flex: 1 }}
                  />
                  <button type="submit" disabled={commentLoading === post.id} style={{ fontSize: 12 }}>
                    {commentLoading === post.id ? '...' : 'Envoyer'}
                  </button>
                </form>

                {/* Liste des commentaires */}
                {comments.length > 0 && (
                  <ul style={{ marginTop: 8, paddingLeft: 16, fontSize: 12, color: '#444' }}>
                    {comments.map((c) => {
                      const isEditing = editingCommentValues[c.id] !== undefined;
                      const currentValue = editingCommentValues[c.id] ?? c.content;
                      const isSaving = editingLoading[c.id] === true;

                      return (
                        <li key={c.id} style={{ marginBottom: 4 }}>
                          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                            <span style={{ fontWeight: 'bold' }}>
                              {c.user?.name ?? 'Anonyme'}:
                            </span>

                            {isEditing ? (
                              <>
                                <input
                                  type="text"
                                  value={currentValue}
                                  onChange={(e) =>
                                    setEditingCommentValues((prev) => ({
                                      ...prev,
                                      [c.id]: e.target.value,
                                    }))
                                  }
                                  style={{ flex: 1 }}
                                />
                                <button
                                  type="button"
                                  onClick={() => saveEditingComment(post.id, c)}
                                  disabled={isSaving}
                                  style={{ fontSize: 11 }}
                                >
                                  {isSaving ? '...' : 'Enregistrer'}
                                </button>
                                <button
                                  type="button"
                                  onClick={() => cancelEditingComment(c.id)}
                                  disabled={isSaving}
                                  style={{ fontSize: 11 }}
                                >
                                  Annuler
                                </button>
                              </>
                            ) : (
                              <>
                                <span>{c.content}</span>
                                <button
                                  type="button"
                                  onClick={() => startEditingComment(c)}
                                  style={{ fontSize: 11 }}
                                >
                                  Modifier
                                </button>
                              </>
                            )}

                            <span style={{ color: '#999', marginLeft: 'auto' }}>
                              {new Date(c.created_at).toLocaleTimeString()}
                            </span>
                          </div>
                        </li>
                      );
                    })}
                  </ul>
                )}
              </li>
            );
          })}
        </ul>

        <div style={{ marginTop: 12, display: 'flex', gap: 8 }}>
          <button
            disabled={state.page <= 1 || state.loading}
            onClick={() => load(state.page - 1)}
          >
            Précédent
          </button>
          <span>
            Page {state.page} / {state.lastPage}
          </span>
          <button
            disabled={state.page >= state.lastPage || state.loading}
            onClick={() => load(state.page + 1)}
          >
            Suivant
          </button>
        </div>
      </section>
    </div>
  );
}
