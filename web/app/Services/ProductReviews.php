<?php

declare(strict_types=1);

namespace Store\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Support\Collection;

final class ProductReviews
{
    private const PURCHASED_ORDER_STATUSES = ['delivered', 'paid'];

    /** @return Collection<int, ProductReview> */
    public function list(Product $product): Collection
    {
        return ProductReview::query()
            ->where('product_id', $product->id)
            ->with('user')
            ->latest('id')
            ->get();
    }

    /** @return array{can_review: bool, reason: 'login'|'purchase'|'eligible'} */
    public function eligibility(?User $user, Product $product): array
    {
        if ($user === null) {
            return ['can_review' => false, 'reason' => 'login'];
        }

        $purchased = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', static fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereIn('status', self::PURCHASED_ORDER_STATUSES))
            ->exists();

        return ['can_review' => $purchased, 'reason' => $purchased ? 'eligible' : 'purchase'];
    }

    public function findForUser(?User $user, Product $product): ?ProductReview
    {
        if ($user === null) {
            return null;
        }

        return ProductReview::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function findForUserReview(User $user, Product $product, int $reviewId): ?ProductReview
    {
        return ProductReview::query()
            ->whereKey($reviewId)
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function create(User $user, Product $product, int $rating, string $body): ProductReview
    {
        $eligibility = $this->eligibility($user, $product);

        if (!$eligibility['can_review']) {
            throw new \DomainException('Само клиент, който е закупил продукта, може да остави отзив.');
        }

        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Изберете оценка от 1 до 5 звезди.');
        }

        return ProductReview::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => $rating,
            'body' => $body,
        ]);
    }

    public function update(ProductReview $review, int $rating, string $body): ProductReview
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Изберете оценка от 1 до 5 звезди.');
        }

        $review->forceFill(['rating' => $rating, 'body' => $body])->save();

        return $review->fresh() ?? $review;
    }

    /** @return array{count: int, average: float|null} */
    public function summary(Product $product): array
    {
        $summary = ProductReview::query()
            ->where('product_id', $product->id)
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating')
            ->first();

        $count = (int) ($summary?->review_count ?? 0);

        return [
            'count' => $count,
            'average' => $count > 0 ? round((float) $summary->average_rating, 1) : null,
        ];
    }

    /** @return array{id: int, rating: int, body: string, author: string, created_at: string, created_at_iso: string} */
    public function payload(ProductReview $review): array
    {
        $review->loadMissing('user');
        $author = trim((string) ($review->user?->first_name ?? 'Клиент'));
        $lastName = trim((string) ($review->user?->last_name ?? ''));

        if ($lastName !== '') {
            $author .= ' ' . $lastName;
        }

        return [
            'id' => (int) $review->id,
            'rating' => max(1, min(5, (int) $review->rating)),
            'body' => (string) $review->body,
            'author' => $author,
            'created_at' => (string) $review->created_at?->timezone('Europe/Sofia')->format('d.m.Y'),
            'created_at_iso' => (string) $review->created_at?->toIso8601String(),
        ];
    }
}
