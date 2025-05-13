import React, { useState, useEffect } from 'react';
import {
  Box,
  Heading,
  VStack,
  HStack,
  Text,
  Image,
  Divider,
  Badge,
  Flex,
  Spinner,
  useColorModeValue,
  Card,
  CardHeader,
  CardBody,
  Icon,
  SimpleGrid,
  Tooltip,
  Center,
  ScaleFade,
  Button,
  useToast,
  IconButton
} from '@chakra-ui/react';
import { FaShoppingCart, FaCoins, FaCalendarAlt, FaUndo } from 'react-icons/fa';
import { TimerService } from '../services/firebase';
import { PurchaseHistory, MarketplaceItem } from '../types';

const timerService = new TimerService();

interface RecentPurchasesProps {
  limit?: number;
  showHeading?: boolean;
}

const RecentPurchases: React.FC<RecentPurchasesProps> = ({ 
  limit = 5,
  showHeading = true 
}) => {
  const [purchases, setPurchases] = useState<PurchaseHistory[]>([]);
  const [items, setItems] = useState<Record<string, MarketplaceItem>>({});
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [isRefunding, setIsRefunding] = useState<Record<string, boolean>>({});
  const toast = useToast();

  // Colors for theming
  const cardBg = useColorModeValue('white', 'gray.800');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const headingColor = useColorModeValue('gray.700', 'white');
  const priceColor = useColorModeValue('green.600', 'green.300');
  const negativePriceColor = useColorModeValue('red.600', 'red.300');
  const subtleTextColor = useColorModeValue('gray.600', 'gray.400');
  const itemBgGradient = useColorModeValue(
    'linear-gradient(to right, purple.50, blue.50)',
    'linear-gradient(to right, purple.900, blue.900)'
  );
  const placeholderBg = useColorModeValue('gray.100', 'gray.700');

  useEffect(() => {
    // Subscribe to purchase history
    const unsubscribePurchases = timerService.subscribeToPurchaseHistory((purchaseHistory) => {
      // Sort by purchase date (newest first) and limit the number of purchases
      const recentPurchases = [...purchaseHistory].sort((a, b) => b.purchasedAt - a.purchasedAt).slice(0, limit);
      setPurchases(recentPurchases);
      setIsLoading(false);
    });

    // Subscribe to marketplace items to get images
    const unsubscribeItems = timerService.subscribeToMarketplaceItems((marketplaceItems) => {
      // Convert array to object with id as key for easier lookup
      const itemsMap: Record<string, MarketplaceItem> = {};
      marketplaceItems.forEach(item => {
        itemsMap[item.id] = item;
      });
      setItems(itemsMap);
    });

    return () => {
      unsubscribePurchases();
      unsubscribeItems();
    };
  }, [limit]);

  const formatDate = (timestamp: number): string => {
    const date = new Date(timestamp);
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric', 
      year: 'numeric' 
    });
  };

  const formatRelativeTime = (timestamp: number): string => {
    const now = new Date();
    const date = new Date(timestamp);
    const diffMs = now.getTime() - date.getTime();
    
    // Convert to seconds, minutes, hours, days
    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    // Format relative time
    if (diffDays > 30) {
      const diffMonths = Math.floor(diffDays / 30);
      return `${diffMonths} month${diffMonths !== 1 ? 's' : ''} ago`;
    } else if (diffDays > 0) {
      return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
    } else if (diffHours > 0) {
      return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
    } else if (diffMins > 0) {
      return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
    } else {
      return 'just now';
    }
  };

  // Handle refund
  const handleRefund = async (purchase: PurchaseHistory) => {
    try {
      setIsRefunding({...isRefunding, [purchase.id]: true});
      
      // Create a deduction session but with positive earnings (refund)
      await timerService.refundPurchase(purchase.id, purchase.itemId, purchase.itemName, purchase.price);
      
      toast({
        title: 'Refund Successful',
        description: `$${purchase.price.toFixed(2)} has been refunded to your account.`,
        status: 'success',
        duration: 3000,
        isClosable: true,
      });
    } catch (error) {
      console.error('Error refunding purchase:', error);
      toast({
        title: 'Refund Failed',
        description: 'Could not process refund. Please try again.',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    } finally {
      setIsRefunding({...isRefunding, [purchase.id]: false});
    }
  };

  if (isLoading) {
    return (
      <Center py={10}>
        <Spinner size="md" color="blue.500" />
      </Center>
    );
  }

  if (purchases.length === 0) {
    return (
      <Box>
        {showHeading && <Heading size="md" mb={4} color={headingColor}>Recent Purchases</Heading>}
        <Card 
          bg={cardBg} 
          borderRadius="lg" 
          borderWidth="1px" 
          borderColor={borderColor}
          boxShadow="sm"
          p={4}
        >
          <Center py={6} flexDirection="column">
            <Icon as={FaShoppingCart} boxSize={10} color="gray.400" mb={3} />
            <Text color={subtleTextColor} fontSize="sm">
              No purchases yet. Visit the shop to buy something!
            </Text>
          </Center>
        </Card>
      </Box>
    );
  }

  return (
    <Box width="100%">
      {showHeading && (
        <Flex justify="space-between" align="center" mb={4}>
          <Heading size="md" color={headingColor}>Recent Purchases</Heading>
          <Badge colorScheme="purple" px={2} py={1} borderRadius="md">
            {purchases.length} item{purchases.length !== 1 ? 's' : ''}
          </Badge>
        </Flex>
      )}

      <VStack spacing={3} align="stretch">
        {purchases.map((purchase, index) => {
          const isNegative = purchase.price < 0;
          const displayPrice = isNegative ? -purchase.price : purchase.price;
          
          // Get item image if available
          const item = items[purchase.itemId];
          const hasImage = item && item.imageUrl;
          
          return (
            <ScaleFade key={purchase.id} initialScale={0.9} in={true}>
              <Card 
                bg={cardBg} 
                borderRadius="lg" 
                borderWidth="1px" 
                borderColor={borderColor}
                boxShadow="sm"
                overflow="hidden"
                transition="transform 0.2s, box-shadow 0.2s"
                _hover={{ 
                  transform: "translateY(-2px)", 
                  boxShadow: "md",
                  borderColor: "blue.300"
                }}
              >
                <CardBody p={3}>
                  <Flex gap={3} align="center">
                    {/* Display item image if available, otherwise fallback to shopping cart icon */}
                    {hasImage ? (
                      <Box 
                        w="50px" 
                        h="50px" 
                        borderRadius="md"
                        overflow="hidden"
                      >
                        <Image 
                          src={item.imageUrl}
                          alt={purchase.itemName}
                          width="100%"
                          height="100%"
                          objectFit="cover"
                        />
                      </Box>
                    ) : (
                      <Center 
                        w="50px" 
                        h="50px" 
                        bg={itemBgGradient}
                        borderRadius="md"
                        color="white"
                        fontSize="xl"
                      >
                        <Icon as={FaShoppingCart} />
                      </Center>
                    )}
                    
                    <Box flex="1">
                      <Flex justify="space-between" align="center">
                        <Text fontWeight="600" fontSize="md" noOfLines={1}>
                          {isNegative ? `${purchase.itemName} (Refund)` : purchase.itemName}
                        </Text>
                        
                        {/* Refund button */}
                        {!isNegative && (
                          <Tooltip label="Refund this purchase">
                            <Button
                              aria-label="Refund purchase"
                              size="xs"
                              colorScheme="red"
                              variant="solid"
                              isLoading={isRefunding[purchase.id]}
                              onClick={() => handleRefund(purchase)}
                            >
                              Refund!
                            </Button>
                          </Tooltip>
                        )}
                      </Flex>
                      
                      <HStack spacing={3} mt={1}>
                        <HStack spacing={1} fontSize="xs" color={subtleTextColor}>
                          <Icon as={FaCalendarAlt} boxSize={3} />
                          <Tooltip label={formatDate(purchase.purchasedAt)}>
                            <Text>{formatRelativeTime(purchase.purchasedAt)}</Text>
                          </Tooltip>
                        </HStack>
                        <HStack spacing={1} fontSize="xs" color={negativePriceColor}>
                          <Icon as={FaCoins} boxSize={3} />
                          <Text fontWeight="bold">
                            -${displayPrice.toFixed(2)}
                          </Text>
                        </HStack>
                      </HStack>
                    </Box>
                  </Flex>
                </CardBody>
              </Card>
            </ScaleFade>
          );
        })}
      </VStack>
    </Box>
  );
};

export default RecentPurchases; 