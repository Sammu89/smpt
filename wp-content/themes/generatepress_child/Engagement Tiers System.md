# SMPT Engagement Tiers System

## Tier Structure (200 Points = 200 Episodes)

| Tier | Points | Name | Daily Limit | Depends On |
|---|---|---|---|---|
| 0 | 0-40 | Membro | 5 eps/day | Day 1 (signup) |
| 1 | 40-80 | Temporada Clássica | 10 eps/day | Interactions |
| 2 | 80-120 | Temporada R | 15 eps/day | Interactions |
| 3 | 120-160 | Temporada S | 20 eps/day | Interactions |
| 4 | 160-200 | SuperS | 25 eps/day | Interactions |
| 5 | 200+ | Sailor Stars | Unlimited | Interactions |

**Unlock speed depends entirely on user engagement:** Comments, likes, ratings—frequency determines progression.

---

## Point Rewards (Daily Cap: 50 pts/24h)

| Action | Points | Throttle |
|---|---|---|
| **Comment** (episode or torrent) | +10 pts | 4-5 burst → 30-min cooldown |
| **Like** (episode or torrent) | +2 pts | 4-5 burst → 30-min cooldown |
| **Rate** (episode) | +6 pts | 4-5 burst → 30-min cooldown |
| **Dislike** (episode) | +2 pts | 4-5 burst → 30-min cooldown |

**50-point daily cap:** If user accumulates 50+ pts in 24h, further actions are blocked until cap resets.

---

## View/Download Unified Limit

| Tier | Daily Limit |
|---|---|
| 0 (Membro) | 5 episodes |
| 1 (Clássica) | 10 episodes |
| 2 (R) | 15 episodes |
| 3 (S) | 20 episodes |
| 4 (SuperS) | 25 episodes |
| 5 (Sailor Stars) | Unlimited |

**Unified:** Streaming OR downloading = same counter. Mix and match.

---

## Interaction Requirements (Per Season)

**Torrent Page Access (magnet link visible if):**
- ✅ User has liked the season's torrent page
- ✅ User has commented on the season's torrent page

---

## Implementation Scope

**Database:**
- `wp_smpt_user_points` (user_id, total_points, current_tier, views_today, last_view_date)
- `wp_smpt_point_log` (user_id, action, points_awarded, created_at)
- `wp_smpt_interaction_throttle` (user_id, episode_id, action_type, last_action_at)

**Frontend:**
- Button disabling when view limit hit
- Link to `/painel/` when disabled
- Throttle tooltips on interaction buttons
- Daily points cap message

**Backend:**
- Point calculation on interaction hooks
- Tier calculation
- View limit check before streaming/downloading
- Throttle check per action type
