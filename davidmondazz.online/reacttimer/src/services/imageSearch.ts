// Define the search result interface
export interface SearchResult {
  id: string;
  url: string;
  thumb: string;
  description: string;
  authorName: string;
  authorUrl: string;
}

/**
 * Search for images using the Pexels API
 */
export const searchImages = async (query: string, limit: number = 9): Promise<SearchResult[]> => {
  try {
    // Add a random parameter to ensure new results on each search
    const page = Math.floor(Math.random() * 5) + 1; // Random page between 1-5
    
    const response = await fetch(
      `https://api.pexels.com/v1/search?query=${encodeURIComponent(query)}&per_page=${limit}&page=${page}&orientation=landscape`,
      {
        headers: {
          // Using the user's Pexels API key
          'Authorization': 'f1kVNcxeP3b5p4Bs9oQnFRuA56tcWlre40u8fBIn0nSTPA323SwNraw5'
        }
      }
    );

    // Check if the response is OK
    if (!response.ok) {
      throw new Error(`Pexels API error: ${response.status} ${response.statusText}`);
    }

    // Parse the response
    const data = await response.json();
    
    // Shuffle the results to add more randomness
    const shuffledPhotos = [...data.photos].sort(() => Math.random() - 0.5);
    
    // Map the Pexels response to our SearchResult interface
    return shuffledPhotos.map((photo: any) => ({
      id: photo.id.toString(),
      url: photo.src.large,
      thumb: photo.src.medium,
      description: photo.alt || query,
      authorName: photo.photographer,
      authorUrl: photo.photographer_url
    }));
  } catch (error) {
    console.error('Error searching images:', error);
    
    // Fallback to local placeholders if the API fails
    return Array.from({ length: limit }, (_, i) => ({
      id: `placeholder-${i}-${Date.now()}`, // Add timestamp for uniqueness
      url: `https://picsum.photos/800/600?random=${i + 1}&t=${Date.now()}`, // Add timestamp to force refresh
      thumb: `https://picsum.photos/400/300?random=${i + 1}&t=${Date.now()}`,
      description: `${query} image ${i + 1}`,
      authorName: 'Lorem Picsum',
      authorUrl: 'https://picsum.photos'
    }));
  }
};